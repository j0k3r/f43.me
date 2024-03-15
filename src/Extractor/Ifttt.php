<?php

namespace App\Extractor;

class Ifttt extends AbstractExtractor
{
    /** @var string */
    protected $recipeId;

    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (!str_starts_with((string) $host, 'ifttt.com')) {
            return false;
        }

        // match recipe id
        preg_match('/recipes\/([0-9]+)\-?/i', (string) $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->recipeId = $matches[1];

        return true;
    }

    public function getContent(): string
    {
        if (!$this->recipeId) {
            return '';
        }

        try {
            $response = $this->client->get('https://ifttt.com/oembed/?url=https://ifttt.com/recipes/' . $this->recipeId . '&format=json');
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Ifttt extract failed for: ' . $this->recipeId, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!\is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>' . $data['title'] . '</h2><p>' . $data['description'] . '</p><p><a href="https://ifttt.com/recipes/' . $this->recipeId . '"><img src="https://ifttt.com/recipe_embed_img/' . $this->recipeId . '"></a></p></div>';
    }
}
