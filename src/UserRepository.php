<?php

class UserRepository
{
    public static function all()
    {
        return Database::fetchAll('SELECT id, full_name, email, role, category, group_name, is_blocked, created_at FROM users ORDER BY full_name');
    }

    public static function find($id)
    {
        return Database::fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail($email)
    {
        return Database::fetchOne('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public static function setRole($id, $role)
    {
        return Database::execute('UPDATE users SET role = ? WHERE id = ?', [$role, $id]);
    }

    public static function setBlocked($id, $blocked)
    {
        return Database::execute('UPDATE users SET is_blocked = ? WHERE id = ?', [$blocked ? 1 : 0, $id]);
    }

    public static function readers()
    {
        return Database::fetchAll("SELECT id, full_name, email, group_name FROM users WHERE role = 'reader' ORDER BY full_name");
    }
}
