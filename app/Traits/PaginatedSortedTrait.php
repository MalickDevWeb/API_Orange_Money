<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait PaginatedSortedTrait
{
    /**
     * Applique la pagination et le tri à une requête
     */
    public function getPaginatedSorted($query, Request $request, $perPage = 15)
    {
        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Validation des champs de tri autorisés
        $allowedSortFields = $this->getAllowedSortFields();
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }

        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', $perPage);
        $perPage = min(max((int)$perPage, 1), 100); // Min 1, max 100

        return $query->paginate($perPage);
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
