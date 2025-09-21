<?php
// Use the community PHP runtime as a thin wrapper to your existing app.
// We change into the project root so relative includes (config/, includes/, pages/) work.
chdir(dirname(__DIR__));

// Delegate all handling to the existing front controller.
// Your index.php will read $_GET['page'] (set by vercel.json rewrites) and render accordingly.
require_once 'index.php';
