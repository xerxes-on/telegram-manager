<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Enums\AnnouncementStatus;
use App\Filament\Resources\AnnouncementResource;
use DOMDocument;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use DOMXPath;
use Illuminate\Support\Str;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = AnnouncementStatus::IN_PROGRESS;

        $html = $data['body'];
        // Define allowed Telegram HTML tags
        $allowed_tags = [
            'b', 'strong',
            'i', 'em',
            'u',
            's', 'strike',
            'tg-spoiler',
            'code',
            'pre',
            'a',
        ];

        $dom = new DOMDocument();
        // Suppress warnings for malformed HTML fragments
        libxml_use_internal_errors(true);

        // Wrap the HTML in a root element to ensure it's always valid XML for DOMDocument
        // Use a simple <div> or similar, it will be discarded during processing.
        $wrappedHtml = '<div>' . $html . '</div>';
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $output_html = '';
        // Now, instead of looking for 'body', we iterate over the children of the wrapped <div>
        // The first child of the document will be our wrapper div.
        $rootNode = $dom->getElementsByTagName('div')->item(0);

        if ($rootNode) { // Ensure the root node was found
            foreach ($rootNode->childNodes as $node) {
                $output_html .= $this->processDomNode($node, $allowed_tags);
            }
        } else {
            // Fallback if DOM parsing fails unexpectedly for the wrapper.
            // This could happen if the HTML is extremely malformed or empty despite wrapping.
            // In such cases, a simple strip_tags might be a safer fallback.
            $output_html = strip_tags($html, '<' . implode('><', $allowed_tags) . '>');
            // Optionally, replace multiple spaces/newlines with single newlines
            $output_html = preg_replace('/\s{2,}/', ' ', $output_html); // Replace multiple spaces with one
            $output_html = preg_replace('/(\n\s*){2,}/', "\n\n", $output_html); // Multiple newlines to just two
            $output_html = trim($output_html); // Trim leading/trailing whitespace
        }

        $output_html = Str::replace(['<p>', '</p>', '<div>', '</div>'], ["\n", "\n", "\n", "\n"], $output_html);
        $output_html = preg_replace('/(\r\n|\r|\n){3,}/', "\n\n", $output_html); // Reduce multiple newlines to max two
        $output_html = trim($output_html); // Trim any leading/trailing newlines


        $data['body'] = $output_html;

        return $data;
    }

    /**
     * Recursively processes DOM nodes to filter out unsupported tags.
     * Converts unsupported block elements to newlines.
     */
    private function processDomNode(\DOMNode $node, array $allowed_tags): string
    {
        $html = '';

        if ($node->nodeType === XML_TEXT_NODE) {
            // Escape text content. DOMDocument might already do this for attributes, but safer for raw text nodes.
            return htmlspecialchars($node->nodeValue, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
        }

        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower($node->nodeName);

            // Special handling for block-level elements not allowed by Telegram
            // Convert them to newlines. Using \n for each block element, then reducing multiple newlines later.
            if (in_array($tag, ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'br'])) {
                $content = '';
                foreach ($node->childNodes as $child) {
                    $content .= $this->processDomNode($child, $allowed_tags);
                }
                // Add a newline for each block element, but avoid excessive newlines if empty content
                return ($content ? $content : '') . ($tag === 'br' ? "\n" : ''); // BR always adds a newline
            }

            // Allowed tags
            if (in_array($tag, $allowed_tags)) {
                $attributes = '';
                if ($tag === 'a' && $node->hasAttributes()) {
                    foreach ($node->attributes as $attr) {
                        if (strtolower($attr->name) === 'href') {
                            $attributes .= ' href="' . htmlspecialchars($attr->value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false) . '"';
                            break;
                        }
                    }
                }

                $content = '';
                foreach ($node->childNodes as $child) {
                    $content .= $this->processDomNode($child, $allowed_tags);
                }
                return "<{$tag}{$attributes}>{$content}</{$tag}>";
            }
            // If tag is not allowed and not a special block element, strip its tags but keep content
            else {
                foreach ($node->childNodes as $child) {
                    $html .= $this->processDomNode($child, $allowed_tags);
                }
                return $html;
            }
        }

        return $html;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->body(__('filament.announcement.messages.created'))
            ->success()
            ->send();
    }
}
