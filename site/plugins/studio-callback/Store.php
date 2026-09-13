<?php

namespace Studio\Callback;

use Kirby\Cms\App;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Exception\NotFoundException;
use PDO;
use RuntimeException;

final class Store
{
    public const STATUSES = ['new' => 'Новые', 'progress' => 'В работе', 'done' => 'Завершённые', 'spam' => 'Спам'];
    private PDO $db;

    public static function directory(App $kirby): string
    {
        return (string)$kirby->option('studio.callback.storage', getenv('STUDIO_CALLBACK_STORAGE_DIR') ?: '');
    }

    public static function for(App $kirby): self
    {
        return new self(self::directory($kirby), $kirby->root('index'));
    }

    public function __construct(string $directory, string $publicRoot)
    {
        // Require a provisioned private directory; resolve symlinks before checking it.
        $directory = $directory !== '' ? realpath($directory) : false;
        $publicRoot = realpath($publicRoot);
        if (!$directory || !$publicRoot || !is_dir($directory) || !is_writable($directory)
            || $directory === $publicRoot || str_starts_with($directory, $publicRoot . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Хранилище заявок недоступно. Обратитесь к администратору.');
        }
        $path = $directory . '/callbacks.sqlite';
        if (is_link($path)) {
            throw new RuntimeException('Хранилище заявок недоступно. Обратитесь к администратору.');
        }
        $mask = umask(0077);
        try {
            $this->db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            chmod($path, 0600);
            $this->db->exec('PRAGMA busy_timeout = 5000');
            $this->db->exec('PRAGMA secure_delete = ON');
            $this->db->exec('CREATE TABLE IF NOT EXISTS callbacks (
                id TEXT PRIMARY KEY, created TEXT NOT NULL, name TEXT NOT NULL, telephone TEXT NOT NULL,
                email TEXT NOT NULL, source_title TEXT NOT NULL, source_url TEXT NOT NULL,
                consent INTEGER NOT NULL, status TEXT NOT NULL DEFAULT "new", notes TEXT NOT NULL DEFAULT "",
                revision INTEGER NOT NULL DEFAULT 1, updated TEXT NOT NULL, updated_by TEXT NOT NULL DEFAULT "",
                email_status TEXT NOT NULL DEFAULT "pending"
            )');
            $this->db->exec('CREATE INDEX IF NOT EXISTS callbacks_status_created ON callbacks(status, created DESC, id DESC)');
            $this->db->exec('CREATE INDEX IF NOT EXISTS callbacks_created ON callbacks(created DESC, id DESC)');
        } finally {
            umask($mask);
        }
    }

    public function create(array $values, string $sourceTitle, string $sourceUrl): string
    {
        $id = bin2hex(random_bytes(16));
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $statement = $this->db->prepare('INSERT INTO callbacks (id, created, updated, name, telephone, email, source_title, source_url, consent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
        $statement->execute([$id, $now, $now, $values['name'], $values['telephone'], $values['email'], $sourceTitle, $sourceUrl]);
        return $id;
    }

    public function emailStatus(string $id, bool $sent): void
    {
        $this->db->prepare('UPDATE callbacks SET email_status = ? WHERE id = ?')->execute([$sent ? 'sent' : 'failed', $id]);
    }

    public function find(string $id): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $id)) throw new NotFoundException();
        $statement = $this->db->prepare('SELECT * FROM callbacks WHERE id = ?');
        $statement->execute([$id]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: throw new NotFoundException();
    }

    public function listing(string $status, int $page = 1): array
    {
        if ($status !== 'all' && !isset(self::STATUSES[$status])) throw new InvalidArgumentException(message: 'Неизвестный статус заявки.');
        $where = $status === 'all' ? '' : ' WHERE status = ?';
        $params = $status === 'all' ? [] : [$status];
        $count = $this->db->prepare('SELECT COUNT(*) FROM callbacks' . $where);
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $limit = 20;
        $page = max(1, min($page, (int)ceil($total / $limit) ?: 1));
        $offset = ($page - 1) * $limit;
        $statement = $this->db->prepare('SELECT id, created, name, telephone, status FROM callbacks' . $where . ' ORDER BY created DESC, id DESC LIMIT ' . $limit . ' OFFSET ' . $offset);
        $statement->execute($params);
        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($this->db->query('SELECT status, COUNT(*) AS total FROM callbacks GROUP BY status') as $row) $counts[$row['status']] = (int)$row['total'];
        return ['records' => $statement->fetchAll(PDO::FETCH_ASSOC), 'counts' => $counts, 'pagination' => compact('total', 'limit', 'page', 'offset')];
    }

    public function update(string $id, array $input, string $userId): void
    {
        $this->find($id);
        $status = $input['status'] ?? null;
        $notes = $input['notes'] ?? null;
        $revision = filter_var($input['revision'] ?? null, FILTER_VALIDATE_INT);
        if (!is_string($status) || !isset(self::STATUSES[$status]) || !is_string($notes) || mb_strlen($notes) > 5000 || !$revision) {
            throw new InvalidArgumentException(message: 'Проверьте статус и заметку (до 5000 символов).');
        }
        $statement = $this->db->prepare('UPDATE callbacks SET status = ?, notes = ?, updated = ?, updated_by = ?, revision = revision + 1 WHERE id = ? AND revision = ?');
        $statement->execute([$status, trim($notes), gmdate('Y-m-d\TH:i:s\Z'), $userId, $id, $revision]);
        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException(message: 'Заявка уже изменена другим сотрудником. Скопируйте заметку, закройте окно и откройте заявку снова.');
        }
    }

    public function delete(string $id, int $revision): void
    {
        $this->find($id);
        $statement = $this->db->prepare('DELETE FROM callbacks WHERE id = ? AND revision = ?');
        $statement->execute([$id, $revision]);
        if ($statement->rowCount() !== 1) throw new InvalidArgumentException(message: 'Заявка изменилась. Обновите страницу перед удалением.');
    }
}
