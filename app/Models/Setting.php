<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model {
    protected $fillable = ['group','key','value','cast_type','label','description','is_public'];
    protected function casts(): array { return ['is_public'=>'boolean']; }

    public static function get(string $key, mixed $default = null): mixed {
        return Cache::remember("setting_{$key}", 3600, function() use ($key, $default) {
            $s = static::where('key',$key)->first();
            if (!$s) return $default;
            return match($s->cast_type) {
                'bool','boolean' => (bool)$s->value,
                'int','integer'  => (int)$s->value,
                'float'          => (float)$s->value,
                'json','array'   => json_decode($s->value, true),
                default          => $s->value,
            };
        });
    }

    public static function set(string $key, mixed $value): void {
        static::updateOrCreate(['key'=>$key],['value'=>is_array($value)?json_encode($value):$value]);
        Cache::forget("setting_{$key}");
    }
}
