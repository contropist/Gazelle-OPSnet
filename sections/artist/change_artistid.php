<?php

if (!$Viewer->permitted('torrents_edit')) {
    error(403);
}
authorize();

$artMan = new Gazelle\Manager\Artist();
$artist = $artMan->findById((int)($_POST['artistid'] ?? 0));
if (is_null($artist)) {
    error('Please select a valid artist to change.');
}
$new = $artMan->findById((int)($_POST['newartistid'] ?? 0));
if (is_null($new)) {
    $new = $artMan->findByName($_POST['newartistname'] ?? '');
    if (is_null($new)) {
        error('Please enter a valid artist ID number or a valid artist name.');
    }
}

if ($artist->id() == $new->id()) {
    error('You cannot merge an artist with itself.');
}

if (isset($_POST['confirm'])) {
    $new->merge(
        $artist,
        $Viewer,
        new \Gazelle\Manager\Collage(),
        new \Gazelle\Manager\Comment(),
        new \Gazelle\Manager\Request(),
        new \Gazelle\Manager\TGroup(),
        new \Gazelle\Log(),
    );
    header("Location: artist.php?action=edit&artistid={$new->id()}");
    exit;
}

echo $Twig->render('artist/merge.twig', [
    'artist'   => $artist,
    'new'      => $new,
    'viewer'   => $Viewer,
]);
