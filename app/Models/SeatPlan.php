<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SeatPlan extends Model {
    use HasFactory;
    protected $fillable = ['bus_id','name','rows','cols','layout','special_seats','is_default'];
    protected function casts(): array { return ['layout'=>'array','special_seats'=>'array','is_default'=>'boolean']; }
    public function bus() { return $this->belongsTo(Bus::class); }
    public function getSeatLabels(): array {
        $labels = [];
        if (!$this->layout) return $labels;
        foreach ($this->layout as $row) {
            foreach ((array)$row as $cell) {
                if ($cell && $cell !== 'aisle') $labels[] = $cell;
            }
        }
        return $labels;
    }
}
