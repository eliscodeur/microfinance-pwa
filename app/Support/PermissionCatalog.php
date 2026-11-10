<?php

namespace App\Support;

class PermissionCatalog
{
    public static function groups(): array
    {
        return config('role_permissions.groups', []);
    }

    public static function allKeys(): array
    {
        $keys = [];

        foreach (self::groups() as $group) {
            foreach ($group['modules'] as $module) {
                $keys = array_merge($keys, array_keys($module['permissions']));
            }
        }

        return array_values(array_unique($keys));
    }

    public static function expand(array $permissions): array
    {
        $aliases = config('role_permissions.legacy_aliases', []);
        $expanded = $permissions;

        foreach ($permissions as $permission) {
            if (isset($aliases[$permission])) {
                $expanded = array_merge($expanded, $aliases[$permission]);
            }
        }

        return array_values(array_unique($expanded));
    }

    public static function labelsFor(array $permissions): array
    {
        $labels = [];
        $expandedPermissions = self::expand($permissions);

        foreach (self::groups() as $group) {
            foreach ($group['modules'] as $module) {
                foreach ($module['permissions'] as $key => $label) {
                    if (in_array($key, $expandedPermissions, true)) {
                        $labels[] = $module['label'] . ' : ' . $label;
                    }
                }
            }
        }

        return $labels;
    }
}
