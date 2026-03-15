<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model {
    protected $fillable = ['user_id','action','model_type','model_id','old_values','new_values','ip_address','user_agent','url'];
    protected function casts(): array { return ['old_values'=>'array','new_values'=>'array']; }
    public function user() { return $this->belongsTo(User::class); }
    public static function record(string $action, ?Model $model = null, array $old = [], array $new = []): void {
        static::create([
            'user_id'    => auth()->id(),
            'action'     => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id'   => $model?->id,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url'        => request()->fullUrl(),
        ]);
    }
}
