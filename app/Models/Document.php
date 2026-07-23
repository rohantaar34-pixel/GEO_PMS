<?php

// app/Models/Document.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
// Remove this line: use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    public const DOCUMENT_TYPES = [
        'contract' => 'Contract',
        'invoice' => 'Invoice',
        'report' => 'Report',
    ];

    public const CATEGORIES = [
        'financial' => 'Financial',
        'legal' => 'Legal',
        'technical' => 'Technical',
        'administrative' => 'Administrative',
    ];

    // Remove this line: use SoftDeletes;
    
    protected $table = 'documents';
    
    protected $fillable = [
        'document_number',
        'title',
        'description',
        'document_type',
        'category',
        'file_path',
        'scanned_image_path',
        'thumbnail_path',
        'original_filename',
        'file_size',
        'file_extension',
        'mime_type',
        'document_date',
        'expiry_date',
        'status',
        'version',
        'project_id',
        'uploaded_by',
        'view_count',
        'download_count',
        'date_added'
    ];
    
    protected $casts = [
        'document_date' => 'date',
        'expiry_date' => 'date',
        'date_added' => 'datetime',
        'view_count' => 'integer',
        'download_count' => 'integer',
        'version' => 'integer'
    ];
    
    // Remove this line: protected $dates = ['date_added', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['date_added', 'created_at', 'updated_at'];
    
    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    
    // Accessors
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) return 'N/A';
        
        $bytes = (int)$this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }
    
    public function getScannedImageUrlAttribute()
    {
        if ($this->scanned_image_path) {
            return asset('storage/' . $this->scanned_image_path);
        }
        return null;
    }

    public function getDocumentTypeDisplayAttribute(): string
    {
        return $this->formatSelectableValue($this->document_type, 'Other');
    }

    public function getCategoryDisplayAttribute(): string
    {
        return $this->formatSelectableValue($this->category, 'None');
    }

    public function getDocumentTypeOptionValueAttribute(): string
    {
        return $this->optionValue($this->document_type, array_keys(self::DOCUMENT_TYPES), 'contract');
    }

    public function getCategoryOptionValueAttribute(): string
    {
        return $this->optionValue($this->category, array_keys(self::CATEGORIES), '');
    }

    public function getDocumentTypeCustomValueAttribute(): string
    {
        return $this->customSelectableValue($this->document_type, array_keys(self::DOCUMENT_TYPES));
    }

    public function getCategoryCustomValueAttribute(): string
    {
        return $this->customSelectableValue($this->category, array_keys(self::CATEGORIES));
    }

    public function getDocumentTypeCssClassAttribute(): string
    {
        return array_key_exists((string) $this->document_type, self::DOCUMENT_TYPES) ? (string) $this->document_type : 'other';
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }
    
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
    
    // Helper Methods
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }
    
    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }

    public static function documentTypeOptions(): array
    {
        return self::DOCUMENT_TYPES;
    }

    public static function categoryOptions(): array
    {
        return self::CATEGORIES;
    }

    private function optionValue(?string $value, array $standardValues, string $default): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $default;
        }

        if (in_array($value, $standardValues, true) || $value === 'other') {
            return $value;
        }

        return 'other';
    }

    private function customSelectableValue(?string $value, array $standardValues): string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === 'other' || in_array($value, $standardValues, true)) {
            return '';
        }

        return $value;
    }

    private function formatSelectableValue(?string $value, string $fallback): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $fallback;
        }

        if (preg_match('/[A-Z]/', $value)) {
            return $value;
        }

        return Str::headline(str_replace('_', ' ', $value));
    }
}
