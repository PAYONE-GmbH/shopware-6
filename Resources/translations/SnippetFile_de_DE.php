<?php

declare(strict_types=1);

namespace PayonePayment\Resources\translations;

use Shopware\Core\Framework\Snippet\Files\SnippetFileInterface;

class SnippetFile_de_DE implements SnippetFileInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'messages_de_DE.json';
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return __DIR__ . '/messages_de_DE.json';
    }

    /**
     * {@inheritdoc}
     */
    public function getIso(): string
    {
        return 'de_DE';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthor(): string
    {
        return 'Kellerkinder GmbH';
    }

    /**
     * {@inheritdoc}
     */
    public function isBase(): bool
    {
        return true;
    }
}
