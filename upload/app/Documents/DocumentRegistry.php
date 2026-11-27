<?php

namespace App\Documents;

use Illuminate\Contracts\Container\Container;

/** Danh sách các loại chứng từ đã đăng ký trong config/documents.php. */
class DocumentRegistry
{
    /** @var array<string, DocumentType> */
    private array $types = [];

    public function __construct(Container $container, array $classes)
    {
        foreach ($classes as $class) {
            $type = $container->make($class);
            $this->types[$type->key()] = $type;
        }
    }

    /** @return array<string, DocumentType> */
    public function all(): array
    {
        return $this->types;
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    public function get(string $key): DocumentType
    {
        return $this->types[$key] ?? abort(404, 'Loại chứng từ không tồn tại');
    }
}
