<?php

if (!check_perms('admin_view_notifications')) {
    error(403);
}

$notifiedId = null;
$uploaderId = null;
$group    = null;
$tags     = [];
$category = null;
$release  = null;
$torrent  = null;
$result   = null;
$sql      = null;
$args     = null;
$torMan   = new Gazelle\Manager\Torrent;

if (isset($_POST['torrentid'])) {
    $torMan->setTorrentId((int)$_POST['torrentid']);
    [$group, $torrent] = $torMan->torrentInfo();
    if ($group) {
        $tags = explode('|', $group['tagNames']);
        if (!$tags) {
            $tags = [];
        }
        $category = $Categories[$group['CategoryID'] - 1];
        $release = $ReleaseTypes[$group['ReleaseType']];
        $notification = new Gazelle\Notification\Upload(0);
        $notification
            ->addFormat($torrent['Format'])
            ->addEncodings($torrent['Encoding'])
            ->addMedia($torrent['Media'])
            ->addYear($group['Year'], $torrent['RemasterYear'])
            ->addArtists($torMan->artistRole())
            ->addTags($tags)
            ->addCategory($category)
            ->addReleaseType($release);

        $userMan = new Gazelle\Manager\User;
        if (isset($_POST['uploaderid'])) {
            $uploader = $userMan->find(trim($_POST['uploaderid']));
            if ($uploader) {
                $notification->addUser($uploader->id());
            }
        }
        $result = $notification->lookup();

        $notifiedId = null;
        if (isset($_POST['notifiedid'])) {
            $notified = $userMan->find(trim($_POST['notifiedid']));
            if ($notified) {
                $id = $notified->id();
                $result = array_filter($result, function ($r) use ($id) {return $r['user_id'] === $id;});
            }
        }

        if (!empty($result)) {
            foreach ($result as &$r) {
                $r['filter'] = new Gazelle\NotificationFilter($r['filter_id']);
            }
        }
        unset($r);
        $sql = $notification->sql();
        $args = $notification->args();
    }
}

View::show_header("Notifications Sandbox");
echo G::$Twig->render('admin/notification-sandbox.twig', [
    'uploader_id' => $uploaderId,
    'notified_id' => $notifiedId,

    'group'   => $group,
    'torrent' => $torrent,
    'manager' => $torMan,

    'category' => $category,
    'release'  => $release,

    'tags'  => implode(', ', $tags),
    'label' => $torrent['RemasterRecordLabel'] ?? $group['RecordLabel'],
    'year'  => $torrent['RemasterYear'] ?? $group['Year'],

    'result' => $result,
    'sql'    => $sql,
    'args'   => $args,
]);
View::show_footer();
