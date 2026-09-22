<?php

namespace App\Support;

class LegalBodyParser
{
    /**
     * Parse seeded legal copy into headings, paragraphs, and lists.
     *
     * @return list<array{type: 'heading'|'paragraph'|'list', text?: string, items?: list<string>}>
     */
    public function parse(string $body): array
    {
        $blocks = [];
        $paragraph = [];
        $list = [];

        foreach (preg_split("/\r\n|\r|\n/", $body) ?: [] as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $this->flushList($blocks, $list);
                $this->flushParagraph($blocks, $paragraph);

                continue;
            }

            if (str_starts_with($trimmed, '## ')) {
                $this->flushList($blocks, $list);
                $this->flushParagraph($blocks, $paragraph);
                $blocks[] = [
                    'type' => 'heading',
                    'text' => trim(substr($trimmed, 3)),
                ];

                continue;
            }

            if (preg_match('/^[-•]\s+(.+)$/u', $trimmed, $matches) === 1) {
                $this->flushParagraph($blocks, $paragraph);
                $list[] = $matches[1];

                continue;
            }

            $this->flushList($blocks, $list);
            $paragraph[] = $trimmed;
        }

        $this->flushList($blocks, $list);
        $this->flushParagraph($blocks, $paragraph);

        return $blocks;
    }

    /**
     * @param  list<array{type: string, text?: string, items?: list<string>}>  $blocks
     * @param  list<string>  $list
     */
    protected function flushList(array &$blocks, array &$list): void
    {
        if ($list === []) {
            return;
        }

        $blocks[] = [
            'type' => 'list',
            'items' => $list,
        ];
        $list = [];
    }

    /**
     * @param  list<array{type: string, text?: string, items?: list<string>}>  $blocks
     * @param  list<string>  $paragraph
     */
    protected function flushParagraph(array &$blocks, array &$paragraph): void
    {
        if ($paragraph === []) {
            return;
        }

        $blocks[] = [
            'type' => 'paragraph',
            'text' => implode(' ', $paragraph),
        ];
        $paragraph = [];
    }
}
