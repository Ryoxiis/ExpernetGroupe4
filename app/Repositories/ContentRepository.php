<?php

declare(strict_types=1);

namespace App\Repositories;

use RuntimeException;

/** Public catalogue snapshot. Replace this adapter with PDO when persistence is needed. */
final class ContentRepository
{
    private array $content;
    private ?array $sourcePages = null;
    private ?array $sourceMap = null;

    public function __construct(private readonly string $directory)
    {
        $this->content = $this->read('content.json');
    }

    private function read(string $file): array
    {
        $json = file_get_contents($this->directory . '/' . $file);
        if ($json === false) {
            throw new RuntimeException('Données de démonstration indisponibles.');
        }

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function sourcePages(): array
    {
        return $this->sourcePages ??= $this->read('source-pages.json');
    }

    public function sourceMap(): array
    {
        return $this->sourceMap ??= $this->read('source-map.json');
    }

    public function courses(): array
    {
        return $this->content['courses'];
    }

    public function coursesByTitle(): array
    {
        $courses = $this->courses();
        usort($courses, static fn (array $a, array $b): int => strnatcasecmp($a['title'], $b['title']));
        return $courses;
    }

    public function course(string $slug): ?array
    {
        foreach ($this->courses() as $course) {
            if ($course['slug'] === $slug) {
                return $course;
            }
        }
        return null;
    }

    public function articles(): array
    {
        return $this->content['articles'];
    }

    public function article(int $id): ?array
    {
        foreach ($this->articles() as $article) {
            if ($article['id'] === $id) {
                return $article;
            }
        }
        return null;
    }

    public function themes(): array
    {
        return $this->content['themes'];
    }

    public function palettes(): array
    {
        return $this->read('palettes.json');
    }

    public function sessions(): array
    {
        return $this->read('sessions.json');
    }

    /** Only send fields the catalogue actually uses to the browser. */
    public function courseSummaries(): array
    {
        $keys = array_flip(['id', 'title', 'slug', 'path', 'category', 'categories', 'duration', 'audience', 'image']);
        return array_map(static fn (array $course): array => array_intersect_key($course, $keys), $this->courses());
    }
}
