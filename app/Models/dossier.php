<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dossier extends Model
{
    protected $fillable = [
        'client_id',
        'agence',
        'num_sinistre',
        'date_sinistre',
        'date_declaration',
        'expert_nom',
        'expert_id',
        'assure_nom',
        'num_police',
        'compagnie',
        'code_agence',
        'num_chassis',
        'matricule',
        'annee',
        'categorie',
        'date_debut_assurance',
        'date_fin_assurance',
        'carte_grise_photo',
        'declaration_recto_photo',
        'declaration_verso_photo',
        'tiers_nom',
        'tiers_matricule',
        'tiers_code_agence',
        'tiers_num_police',
        'tiers_compagnie',
        'photos_accident',
        'link_pv',
        'link_note',
        'status',
        'admin_comment',
        'note_honoraire_montant',
        'seenbyadmin',
        'adminchangeseen'
    ];

    protected $casts = [
        'date_sinistre' => 'date',
        'date_declaration' => 'date',
        'date_debut_assurance' => 'date',
        'date_fin_assurance' => 'date',
        'photos_accident' => 'array',
        'seenbyadmin' => 'boolean',
        'adminchangeseen' => 'boolean'
    ];

    protected $attributes = [
        'seenbyadmin' => false,
        'adminchangeseen' => true
    ];

    /**
     * Get the client that owns the dossier.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the expert (admin) assigned to this dossier.
     */
    public function expert(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'expert_id');
    }

    /**
     * Get the expert name with fallback to expert_nom field.
     */
    public function getExpertNameAttribute(): ?string
    {
        if ($this->expert) {
            return $this->expert->name;
        }
        
        return $this->expert_nom;
    }

    /**
     * Get the expert reference (ID) if assigned.
     */
    public function getExpertReferenceAttribute(): ?string
    {
        if ($this->expert_id) {
            return 'EXP-' . str_pad($this->expert_id, 4, '0', STR_PAD_LEFT);
        }
        
        return null;
    }
}