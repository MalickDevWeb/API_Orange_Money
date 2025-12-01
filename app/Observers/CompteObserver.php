<?php

namespace App\Observers;

use App\Models\Compte;

class CompteObserver
{
    /**
     * Handle the Compte "creating" event.
     */
    public function creating(Compte $compte): void
    {
        // QR code functionality removed as column was dropped
    }

    /**
     * Génère le contenu SVG du QR code (version compacte)
     */
    private function generateQrCodeSvg(string $data): string
    {
        $size = 120; // Plus petit pour réduire la taille du base64
        $margin = 6;
        $moduleSize = 4; // Modules plus petits

        // Créer une matrice simple qui ressemble à un QR code (15x15)
        $matrix = $this->createCompactQrMatrix($data);

        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="100%" height="100%" fill="white"/>';

        // Dessiner les modules noirs
        foreach ($matrix as $y => $row) {
            foreach ($row as $x => $module) {
                if ($module) {
                    $xPos = $margin + ($x * $moduleSize);
                    $yPos = $margin + ($y * $moduleSize);
                    $svg .= '<rect x="' . $xPos . '" y="' . $yPos . '" width="' . $moduleSize . '" height="' . $moduleSize . '" fill="black"/>';
                }
            }
        }

        // Ajouter le numéro de compte en petit texte
        $text = substr($data, 0, 10);
        $svg .= '<text x="' . ($size/2) . '" y="' . ($size/2 + 3) . '" text-anchor="middle" font-family="Arial,sans-serif" font-size="8" fill="#FF6B35">' . htmlspecialchars($text) . '</text>';

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Génère une vraie image PNG qui ressemble à un QR code
     */
    private function generateQrCodePng(string $data): string
    {
        $size = 120; // Taille de l'image en pixels
        $moduleSize = 8; // Taille de chaque module en pixels
        $margin = 8; // Marge en pixels

        // Calculer la taille de la matrice
        $matrixSize = 15; // 15x15 modules
        $imageSize = $margin * 2 + $matrixSize * $moduleSize;

        // Créer la matrice QR
        $matrix = $this->createCompactQrMatrix($data);

        // Créer une image PNG simple (format binaire)
        $pngData = $this->createSimplePng($matrix, $matrixSize, $moduleSize, $margin, $size);

        return $pngData;
    }

    /**
     * Crée un PNG simple avec un design QR code basique
     */
    private function createSimplePng(array $matrix, int $matrixSize, int $moduleSize, int $margin, int $size): string
    {
        $width = 120;
        $height = 120;

        // Créer une image simple : fond blanc avec des carrés noirs pour ressembler à un QR code
        $pixels = [];

        // Fond blanc
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $pixels[] = 255; // Blanc
            }
        }

        // Ajouter des patterns simples pour ressembler à un QR code
        // Coin supérieur gauche (3x3)
        for ($y = 10; $y < 40; $y += 10) {
            for ($x = 10; $x < 40; $x += 10) {
                $this->drawBlackSquare($pixels, $width, $x, $y, 10);
            }
        }

        // Coin supérieur droit (3x3)
        for ($y = 10; $y < 40; $y += 10) {
            for ($x = 80; $x < 110; $x += 10) {
                $this->drawBlackSquare($pixels, $width, $x, $y, 10);
            }
        }

        // Coin inférieur gauche (3x3)
        for ($y = 80; $y < 110; $y += 10) {
            for ($x = 10; $x < 40; $x += 10) {
                $this->drawBlackSquare($pixels, $width, $x, $y, 10);
            }
        }

        // Quelques modules de données au centre
        $this->drawBlackSquare($pixels, $width, 50, 50, 10);
        $this->drawBlackSquare($pixels, $width, 60, 50, 10);
        $this->drawBlackSquare($pixels, $width, 50, 60, 10);

        return $this->createMinimalPng($pixels, $width, $height);
    }

    /**
     * Dessine un carré noir aux coordonnées spécifiées
     */
    private function drawBlackSquare(array &$pixels, int $width, int $startX, int $startY, int $size): void
    {
        for ($y = $startY; $y < $startY + $size && $y < 120; $y++) {
            for ($x = $startX; $x < $startX + $size && $x < 120; $x++) {
                $index = $y * $width + $x;
                if ($index < count($pixels)) {
                    $pixels[$index] = 0; // Noir
                }
            }
        }
    }

    /**
     * Crée un PNG minimal valide
     */
    private function createMinimalPng(array $pixels, int $width, int $height): string
    {
        // En-tête PNG
        $png = "\x89PNG\r\n\x1a\n";

        // Chunk IHDR (Image Header) - 120x120, 8-bit grayscale
        $ihdr = pack('N', $width) . pack('N', $height) . chr(8) . chr(0) . chr(0) . chr(0) . chr(0);
        $png .= $this->createPngChunk('IHDR', $ihdr);

        // Créer les scanlines (lignes d'image)
        $scanlines = '';
        for ($y = 0; $y < $height; $y++) {
            $scanlines .= chr(0); // Filter type = None
            for ($x = 0; $x < $width; $x++) {
                $index = $y * $width + $x;
                $pixel = $pixels[$index] ?? 255; // Blanc par défaut
                $scanlines .= chr($pixel); // Niveau de gris
            }
        }

        // Compresser avec zlib (niveau 6 pour compatibilité)
        $compressed = gzcompress($scanlines, 6);
        if ($compressed === false) {
            // Fallback sans compression
            $compressed = $scanlines;
        }

        $png .= $this->createPngChunk('IDAT', $compressed);
        $png .= $this->createPngChunk('IEND', '');

        return $png;
    }

    /**
     * Crée un chunk PNG
     */
    private function createPngChunk(string $type, string $data): string
    {
        $length = pack('N', strlen($data));
        $crc = pack('N', crc32($type . $data));
        return $length . $type . $data . $crc;
    }

    /**
     * Sauvegarde le fichier QR code et retourne l'URL publique
     */
    private function saveQrCodeFile(string $data, string $pngData): string
    {
        try {
            $filename = 'qr_' . md5($data) . '.png';
            $filepath = public_path('images/qr-codes/' . $filename);

            // Créer le dossier s'il n'existe pas
            $directory = dirname($filepath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($filepath, $pngData);

            // Retourner l'URL publique absolue
            return asset('images/qr-codes/' . $filename);
        } catch (\Exception $e) {
            // En cas d'erreur, retourner une URL par défaut ou lever l'erreur
            throw $e;
        }
    }

    /**
     * Crée une matrice compacte qui ressemble à un QR code (15x15)
     */
    private function createCompactQrMatrix(string $data): array
    {
        $matrixSize = 15; // Matrice plus petite
        $matrix = array_fill(0, $matrixSize, array_fill(0, $matrixSize, false));

        // Ajouter les patterns de position simplifiés (coins 3x3)
        $this->addCompactSvgPositionPattern($matrix, 0, 0);
        $this->addCompactSvgPositionPattern($matrix, $matrixSize - 3, 0);
        $this->addCompactSvgPositionPattern($matrix, 0, $matrixSize - 3);

        // Ajouter des modules de données basés sur le numéro de compte
        $dataHash = md5($data);
        for ($i = 0; $i < min(strlen($dataHash), 40); $i++) {
            $x = 4 + ($i % 5);
            $y = 4 + intval($i / 5);
            if ($x < $matrixSize - 4 && $y < $matrixSize - 4) {
                $matrix[$y][$x] = (ord($dataHash[$i]) % 2 === 0);
            }
        }

        return $matrix;
    }

    /**
     * Ajoute un pattern de position compact (3x3)
     */
    private function addCompactSvgPositionPattern(array &$matrix, int $startX, int $startY): void
    {
        // Carré 3x3 noir avec centre blanc (pattern compact)
        for ($y = 0; $y < 3; $y++) {
            for ($x = 0; $x < 3; $x++) {
                if ($x === 1 && $y === 1) {
                    $matrix[$startY + $y][$startX + $x] = false; // Centre blanc
                } else {
                    $matrix[$startY + $y][$startX + $x] = true; // Bordure noire
                }
            }
        }
    }

    /**
     * Génère un QR code ASCII simple pour affichage dans les emails (fallback)
     */
    private function generateAsciiQrCode(string $data): string
    {
        // Créer un QR code ASCII compact (11x11) comme Wave/Orange Money
        $size = 11; // Taille compacte pour mobile
        $qr = [];

        // Initialiser la matrice avec des carrés blancs
        for ($i = 0; $i < $size; $i++) {
            $qr[$i] = array_fill(0, $size, '⬜');
        }

        // Ajouter les patterns de position simplifiés (coins 3x3)
        $this->addCompactPositionPattern($qr, 0, 0);
        $this->addCompactPositionPattern($qr, $size - 3, 0);
        $this->addCompactPositionPattern($qr, 0, $size - 3);

        // Encoder les données (numéro de compte) de manière simplifiée
        $dataLength = min(strlen($data), 8); // Limiter pour la taille compacte
        $encodedData = substr($data, 0, $dataLength);

        // Placer les données dans la zone centrale (version très simplifiée)
        for ($i = 0; $i < $dataLength; $i++) {
            $x = 4 + ($i % 3); // Zone centrale
            $y = 4 + intval($i / 3);
            if ($x < $size - 3 && $y < $size - 3 && $x > 2 && $y > 2) {
                $qr[$y][$x] = '⬛';
            }
        }

        // Ajouter quelques motifs fixes pour ressembler à un vrai QR code
        $qr[5][5] = '⬛'; // Point central
        $qr[3][7] = '⬛'; // Quelques points de données
        $qr[7][3] = '⬛';

        // Convertir en chaîne ASCII compacte
        $ascii = "";
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $ascii .= $qr[$y][$x];
            }
            $ascii .= "\n";
        }

        // Format compact pour email
        return $ascii . "\n**" . $data . "**";
    }

    /**
     * Ajoute un pattern de position compact (3x3) au QR code
     */
    private function addCompactPositionPattern(array &$qr, int $startX, int $startY): void
    {
        // Carré 3x3 noir avec centre blanc (pattern compact)
        for ($y = 0; $y < 3; $y++) {
            for ($x = 0; $x < 3; $x++) {
                if ($x === 1 && $y === 1) {
                    $qr[$startY + $y][$startX + $x] = '⬜'; // Centre blanc
                } else {
                    $qr[$startY + $y][$startX + $x] = '⬛'; // Bordure noire
                }
            }
        }
    }
}
