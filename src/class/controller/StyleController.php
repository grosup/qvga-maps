<?php
/**
 * Style Controller
 * Handles map style selection and updates session
 * OOP implementation replacing procedural mapstyle.php
 */

namespace NokiaMaps\Controller;

use NokiaMaps\Session;

class StyleController
{
    private Session $session;
    private array $allowedStyles;

    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->allowedStyles = ['streets-v12', 'outdoors-v12', 'satellite-v9'];
    }

    /**
     * Handle the style change request
     */
    public function handle(): void
    {
        // Get selected map style from GET or POST
        $style = $_GET['map_style'] ?? ($_POST['map_style'] ?? '');

        // Validate and set style
        if ($this->isValidStyle($style)) {
            $this->session->setMapStyle($style);
        }

        // Redirect back to map
        $this->redirectToMap();
    }

    /**
     * Check if the style is in the allowed list
     */
    private function isValidStyle(string $style): bool
    {
        return !empty($style) && in_array($style, $this->allowedStyles, true);
    }

    /**
     * Redirect back to the main map page
     */
    private function redirectToMap(): void
    {
        header('Location: ../index.php');
        exit();
    }
}
