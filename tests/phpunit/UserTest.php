<?php

use \PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../../lib/bootstrap.php');
require_once(__DIR__ . '/../helper.php');

use \Gazelle\Enum\AvatarDisplay;
use \Gazelle\Enum\AvatarSynthetic;

class UserTest extends TestCase {
    protected \Gazelle\User $user;

    public function setUp(): void {
        $this->user = Helper::makeUser('user.' . randomString(6), 'user');
    }

    public function tearDown(): void {
        $this->user->remove();
    }

    public function modifyAvatarRender(AvatarDisplay $display, AvatarSynthetic $synthetic): int {
        $db = Gazelle\DB::DB();
        $db->prepared_query("
            UPDATE users_info SET SiteOptions = ? WHERE UserID = ?
            ", serialize(['DisableAvatars' => $display->value, 'Identicons' => $synthetic->value]), $this->user->id()
        );
        $affected = $db->affected_rows();
        $this->user->flush();
        return $affected;
    }

    public function testUserFind(): void {
        $userMan = new \Gazelle\Manager\User;
        $admin = $userMan->find('@admin');
        $this->assertTrue($admin->isStaff(), 'admin-is-admin');
        $this->assertTrue($admin->permitted('site_upload'), 'admin-permitted-site_upload');
        $this->assertTrue($admin->permitted('site_debug'), 'admin-permitted-site_debug');
        $this->assertTrue($admin->permittedAny('site_analysis', 'site_debug'), 'admin-permitted-any-site_analysis-site_debug');
    }

    public function testFindById(): void {
        $userMan = new \Gazelle\Manager\User;
        $user = $userMan->findById(2);
        $this->assertFalse($user->isStaff(), 'user-is-not-admin');
        $this->assertEquals($user->username(), 'user', 'user-username');
        $this->assertEquals($user->email(), 'user@example.com', 'user-email');
        $this->assertTrue($user->isEnabled(), 'user-is-enabled');
        $this->assertFalse($user->isUnconfirmed(), 'user-is-confirmed');
        $this->assertFalse($user->permittedAny('site_analysis', 'site_debug'), 'utest-permittedAny-site-analysis-site-debug');
    }

    public function testAttr(): void {
        $userMan = new \Gazelle\Manager\User;
        $this->assertFalse($this->user->hasUnlimitedDownload(), 'uattr-hasUnlimitedDownload');
        $this->user->toggleUnlimitedDownload(true);
        $this->assertTrue($this->user->hasUnlimitedDownload(), 'uattr-not-hasUnlimitedDownload');

        $this->assertTrue($this->user->hasAcceptFL(), 'uattr-has-FL');
        $this->user->toggleAcceptFL(false);
        $this->assertFalse($this->user->hasAcceptFL(), 'uattr-has-not-FL');

        $this->assertNull($this->user->option('nosuchoption'), 'uattr-nosuchoption');

        $this->assertEquals($this->user->avatarMode(), AvatarDisplay::show, 'uattr-avatarMode');
        $this->assertEquals($this->user->bonusPointsTotal(), 0, 'uattr-bp');
        $this->assertEquals($this->user->downloadedSize(), 0, 'uattr-starting-download');
        $this->assertEquals($this->user->postsPerPage(), POSTS_PER_PAGE, 'uattr-ppp');
        $this->assertEquals($this->user->uploadedSize(), STARTING_UPLOAD, 'uattr-starting-upload');
        $this->assertEquals($this->user->userclassName(), 'User', 'uattr-userclass-name');

        $this->assertFalse($this->user->disableAvatar(), 'uattr-disableAvatar');
        $this->assertFalse($this->user->disableBonusPoints(), 'uattr-disableBonusPoints');
        $this->assertFalse($this->user->disableForums(), 'uattr-disableForums');
        $this->assertFalse($this->user->disableInvites(), 'uattr-disableInvites');
        $this->assertFalse($this->user->disableIRC(), 'uattr-disableIRC');
        $this->assertFalse($this->user->disablePm(), 'uattr-disablePm');
        $this->assertFalse($this->user->disablePosting(), 'uattr-disablePosting');
        $this->assertFalse($this->user->disableRequests(), 'uattr-disableRequests');
        $this->assertFalse($this->user->disableTagging(), 'uattr-disableTagging');
        $this->assertFalse($this->user->disableUpload(), 'uattr-disableUpload');
        $this->assertFalse($this->user->disableWiki(), 'uattr-disableWiki');

        $this->assertFalse($this->user->hasAttr('disable-forums'), 'uattr-hasAttr-disable-forums-no');
        $this->user->toggleAttr('disable-forums', true);
        $this->assertTrue($this->user->hasAttr('disable-forums'), 'uattr-toggle-disable-forums');
        $this->assertTrue($this->user->disableForums(), 'uattr-hasAttr-disable-forums-yes');
    }

    public function testPassword(): void {
        $userMan = new \Gazelle\Manager\User;
        $password = randomString(30);
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit';
        $this->assertTrue($this->user->updatePassword($password, '0.0.0.0'), 'utest-password-modify');
        $this->assertTrue($this->user->validatePassword($password), 'utest-password-validate-new');
        $this->assertCount(1, $this->user->passwordHistory(), 'utest-password-history');
        $this->assertEquals($this->user->passwordCount(), 1, 'utest-password-count');
    }

    public function testUser(): void {
        $userMan = new \Gazelle\Manager\User;
        $this->assertEquals($this->user->username(), $this->user->flush()->username(), 'utest-flush-username');

        $this->assertEquals($this->user->primaryClass(), USER, 'utest-primary-class');
        $this->assertEquals($this->user->inboxUnreadCount(), 0, 'utest-inbox-unread');
        $this->assertEquals($this->user->allowedPersonalCollages(), 0, 'utest-personal-collages-allowed');
        $this->assertEquals($this->user->paidPersonalCollages(), 0, 'utest-personal-collages-paid');
        $this->assertEquals($this->user->activePersonalCollages(), 0, 'utest-personal-collages-active');
        $this->assertEquals($this->user->collagesCreated(), 0, 'utest-collage-created');
        $this->assertEquals($this->user->pendingInviteCount(), 0, 'utest-personal-collages-active');
        $this->assertEquals($this->user->seedingSize(), 0, 'utest-personal-collages-active');

        $this->assertTrue($this->user->isVisible(), 'utest-is-visble');
        $this->assertTrue($this->user->canLeech(), 'can-leech');
        $this->assertTrue($this->user->permitted('site_upload'), 'utest-permitted-site-upload');
        $this->assertTrue($this->user->permittedAny('site_upload', 'site_debug'), 'utest-permittedAny-site-upload-site-debug');

        $this->assertFalse($this->user->isDisabled(), 'utest-is-disabled');
        $this->assertFalse($this->user->isFLS(), 'utest-is-fls');
        $this->assertFalse($this->user->isInterviewer(), 'utest-is-interviewer');
        $this->assertFalse($this->user->isLocked(), 'utest-is-locked');
        $this->assertFalse($this->user->isRecruiter(), 'utest-is-recruiter');
        $this->assertFalse($this->user->isStaff(), 'utest-is-staff');
        $this->assertFalse($this->user->isStaffPMReader(), 'utest-is-staff-pm-reader');
        $this->assertFalse($this->user->isWarned(), 'utest-is-warned');
        $this->assertFalse($this->user->canCreatePersonalCollage(), 'utest-personal-collage-create');
        $this->assertFalse($this->user->permitted('site_debug'), 'utest-permitted-site-debug');

        $this->assertNull($this->user->warningExpiry(), 'utest-warning-expiry');

        $this->assertCount(0, $this->user->announceKeyHistory(), 'utest-announce-key-history');
    }

    public function testAvatar(): void {
        $userMan = new \Gazelle\Manager\User;
        $this->assertEquals('', $this->user->avatar(), 'utest-avatar-blank');
        $this->assertEquals(
            [
                'image' => USER_DEFAULT_AVATAR,
                'hover' => false,
                'text'  => false,
            ],
            $this->user->avatarComponentList($this->user),
            'utest-avatar-default'
        );

        // defeat the avatar cache
        $this->assertEquals(1, $this->modifyAvatarRender(AvatarDisplay::none, AvatarSynthetic::robot1), 'utest-avatar-update-none');
        $this->assertEquals(AvatarDisplay::none, $this->user->avatarMode(), 'utest-has-avatar-none');
        $new = $userMan->findById($this->user->id());
        $this->assertEquals(USER_DEFAULT_AVATAR, $new->avatarComponentList($this->user->flush())['image'], 'utest-avatar-none');

        $this->assertEquals(1, Helper::modifyUserAvatar($this->user, 'https://www.example.com/avatar.jpg'), 'utest-avatar-set');
        $this->assertEquals('https://www.example.com/avatar.jpg', $this->user->avatar(), 'utest-avatar-url');
        $new = $userMan->findById($this->user->id());
        $this->assertEquals(USER_DEFAULT_AVATAR, $new->avatarComponentList($this->user->flush())['image'], 'utest-avatar-override-none');

        $this->assertEquals(1, $this->modifyAvatarRender(AvatarDisplay::forceSynthetic, AvatarSynthetic::identicon), 'utest-avatar-update-synthetic-identicon');
        $this->assertEquals(AvatarDisplay::forceSynthetic, $this->user->flush()->avatarMode(), 'utest-clone-avatar-forceSynthetic');

        $this->assertEquals(1, $this->modifyAvatarRender(AvatarDisplay::show, AvatarSynthetic::robot1), 'utest-avatar-update-show');
        $new = $userMan->findById($this->user->id());
        $this->assertEquals('https://www.example.com/avatar.jpg', $new->avatarComponentList($this->user->flush())['image'], 'utest-avatar-show');
    }
}
