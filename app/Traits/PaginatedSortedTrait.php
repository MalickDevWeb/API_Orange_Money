<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait PaginatedSortedTrait
{
    /**
     * Applique la pagination et le tri à une requête
     */
    public function getPaginatedSorted($query, Request $request, $perPage = null)
    {
        // Utiliser la pagination configurable si non spécifiée
        if ($perPage === null) {
            $perPage = $this->getDefaultPerPage();
        }

        // Tri
        $sortBy = $request->get('sort_by', $this->getDefaultSortField());
        $sortDirection = $request->get('sort_direction', $this->getDefaultSortDirection());

        // Validation des champs de tri autorisés
        $allowedSortFields = $this->getAllowedSortFields();
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = $this->getDefaultSortField();
        }

        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = $this->getDefaultSortDirection();
        }

        $query->orderBy($sortBy, $sortDirection);

        // Pagination configurable par admin
        $perPage = $request->get('per_page', $perPage);
        $maxPerPage = $this->getMaxPerPage();
        $perPage = min(max((int)$perPage, 1), $maxPerPage);

        return $query->paginate($perPage);
    }

    /**
     * Retourne la pagination par défaut (configurable)
     */
    protected function getDefaultPerPage()
    {
        return config('app.pagination.default_per_page', 15);
    }

    /**
     * Retourne le champ de tri par défaut
     */
    protected function getDefaultSortField()
    {
        return 'created_at';
    }

    /**
     * Retourne la direction de tri par défaut
     */
    protected function getDefaultSortDirection()
    {
        return 'desc';
    }

    /**
     * Retourne le nombre maximum d'éléments par page
     */
    protected function getMaxPerPage()
    {
        return config('app.pagination.max_per_page', 100);
    }

    /**
     * Retourne les champs autorisés pour le tri
     * À surcharger dans les contrôleurs
     */
    protected function getAllowedSortFields()
    {
        return ['created_at', 'updated_at'];
    }
}
