<?php
/**
 * Unit tests for NewCareer Resume & Admin Backend
 *
 * Run: php tests/test_models.php
 * Requires PHP 8.0+
 *
 * Uses an in-memory SQLite database so no MySQL connection is needed for tests.
 */

// ---- Minimal test runner -------------------------------------------------
$passed = 0;
$failed = 0;

function ok(bool $cond, string $label): void {
    global $passed, $failed;
    if ($cond) {
        echo "\033[32m[PASS]\033[0m {$label}\n";
        $passed++;
    } else {
        echo "\033[31m[FAIL]\033[0m {$label}\n";
        $failed++;
    }
}

function throws(callable $fn, string $exceptionClass, string $label): void {
    global $passed, $failed;
    try {
        $fn();
        echo "\033[31m[FAIL]\033[0m {$label} (no exception thrown)\n";
        $failed++;
    } catch (\Throwable $e) {
        if ($e instanceof $exceptionClass) {
            echo "\033[32m[PASS]\033[0m {$label}\n";
            $passed++;
        } else {
            echo "\033[31m[FAIL]\033[0m {$label} (wrong exception: " . get_class($e) . ": " . $e->getMessage() . ")\n";
            $failed++;
        }
    }
}

// ---- SQLite in-memory DB -------------------------------------------------
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Create tables mirroring MySQL schema (SQLite compatible)
$pdo->exec("
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE auth_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE resumes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL DEFAULT 'My Resume',
    summary TEXT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT,
    address TEXT,
    city TEXT,
    state TEXT,
    country TEXT,
    postal_code TEXT,
    linkedin_url TEXT,
    github_url TEXT,
    website_url TEXT,
    is_public INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE education (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resume_id INTEGER NOT NULL,
    institution TEXT NOT NULL,
    degree TEXT,
    field_of_study TEXT,
    start_date TEXT,
    end_date TEXT,
    is_current INTEGER NOT NULL DEFAULT 0,
    description TEXT,
    gpa REAL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE
);

CREATE TABLE experience (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resume_id INTEGER NOT NULL,
    company TEXT NOT NULL,
    position TEXT NOT NULL,
    location TEXT,
    start_date TEXT,
    end_date TEXT,
    is_current INTEGER NOT NULL DEFAULT 0,
    description TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE
);

CREATE TABLE skills (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resume_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    proficiency TEXT DEFAULT 'intermediate',
    category TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE
);

CREATE TABLE projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resume_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    url TEXT,
    start_date TEXT,
    end_date TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE
);

CREATE TABLE certifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resume_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    issuer TEXT,
    issue_date TEXT,
    expiry_date TEXT,
    credential_id TEXT,
    credential_url TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE
);
");

// Load model classes
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Resume.php';
require_once __DIR__ . '/../middleware/auth.php';

$userModel   = new User($pdo);
$resumeModel = new Resume($pdo);
$authMiddle  = new Auth($pdo);

// ========================================================================= //
//  User model tests
// ========================================================================= //
echo "\n── User Model ──────────────────────────────────────────────────────────\n";

$uid = $userModel->create([
    'username' => 'testuser',
    'email'    => 'test@example.com',
    'password' => 'Password1',
    'role'     => 'user',
]);
ok($uid > 0, 'User::create returns new ID');

$u = $userModel->getById($uid);
ok($u !== null,                   'User::getById returns user');
ok($u['username'] === 'testuser', 'User::getById username matches');
ok($u['email'] === 'test@example.com', 'User::getById email matches');
ok($u['role'] === 'user',         'User::getById role is user');
ok(!isset($u['password_hash']),   'User::getById does not expose password_hash');

$byEmail = $userModel->getByEmail('test@example.com');
ok($byEmail !== null,             'User::getByEmail finds user');

$byUsername = $userModel->getByUsername('testuser');
ok($byUsername !== null,          'User::getByUsername finds user');

ok($userModel->verifyPassword($byUsername, 'Password1'), 'User::verifyPassword correct password');
ok(!$userModel->verifyPassword($byUsername, 'wrong'),    'User::verifyPassword wrong password');

throws(
    fn() => $userModel->create(['username'=>'testuser','email'=>'new@example.com','password'=>'Pass123!']),
    InvalidArgumentException::class,
    'User::create rejects duplicate username'
);

throws(
    fn() => $userModel->create(['username'=>'newuser2','email'=>'test@example.com','password'=>'Pass123!']),
    InvalidArgumentException::class,
    'User::create rejects duplicate email'
);

throws(
    fn() => $userModel->create(['username'=>'ab','email'=>'ok@example.com','password'=>'Pass123!']),
    InvalidArgumentException::class,
    'User::create rejects too-short username'
);

throws(
    fn() => $userModel->create(['username'=>'validuser','email'=>'not-an-email','password'=>'Pass123!']),
    InvalidArgumentException::class,
    'User::create rejects invalid email'
);

throws(
    fn() => $userModel->create(['username'=>'validuser2','email'=>'ok2@example.com','password'=>'short']),
    InvalidArgumentException::class,
    'User::create rejects short password'
);

$updated = $userModel->update($uid, ['username' => 'updated_user']);
ok($updated, 'User::update returns true on success');
$u2 = $userModel->getById($uid);
ok($u2['username'] === 'updated_user', 'User::update correctly changes username');

$adminId = $userModel->create([
    'username' => 'adminuser',
    'email'    => 'admin@example.com',
    'password' => 'AdminPass1',
    'role'     => 'admin',
]);
$admin = $userModel->getById($adminId);
ok($admin['role'] === 'admin', 'User::create with admin role');

$page = $userModel->getAll(1, 10);
ok(isset($page['data']) && isset($page['total']), 'User::getAll returns paginated structure');
ok($page['total'] >= 2,                           'User::getAll total >= 2');

$userModel->update($uid, ['is_active' => 0]);
$deactivated = $userModel->getById($uid);
ok((int)$deactivated['is_active'] === 0, 'User::update deactivates user');

// ========================================================================= //
//  Auth middleware tests
// ========================================================================= //
echo "\n── Auth Middleware ──────────────────────────────────────────────────────\n";

$token = $authMiddle->createToken($adminId, 24);
ok(strlen($token) === 64, 'Auth::createToken generates 64-char hex token');

// Simulate token lookup
$stmt = $pdo->prepare(
    "SELECT u.id, u.username, u.email, u.role, u.is_active
     FROM auth_tokens t
     JOIN users u ON u.id = t.user_id
     WHERE t.token = ? AND t.expires_at > datetime('now') AND u.is_active = 1"
);
$stmt->execute([$token]);
$tokenUser = $stmt->fetch();
ok($tokenUser !== false,            'Valid token resolves to user');
ok($tokenUser['role'] === 'admin',  'Token resolves to correct role');

$authMiddle->revokeToken($token);
$stmt->execute([$token]);
ok($stmt->fetch() === false, 'Auth::revokeToken removes token');

$authMiddle->createToken($adminId);
$authMiddle->createToken($adminId);
$authMiddle->revokeAllUserTokens($adminId);
$remaining = $pdo->query("SELECT COUNT(*) FROM auth_tokens WHERE user_id = {$adminId}")->fetchColumn();
ok((int)$remaining === 0, 'Auth::revokeAllUserTokens removes all user tokens');

// ========================================================================= //
//  Resume model tests
// ========================================================================= //
echo "\n── Resume Model ─────────────────────────────────────────────────────────\n";

// Reactivate user for ownership tests
$userModel->update($uid, ['is_active' => 1]);

$rid = $resumeModel->create($uid, [
    'title'      => 'Software Engineer Resume',
    'first_name' => 'John',
    'last_name'  => 'Doe',
    'email'      => 'john@example.com',
    'phone'      => '+1234567890',
    'city'       => 'New York',
    'country'    => 'US',
]);
ok($rid > 0, 'Resume::create returns new ID');

$r = $resumeModel->getById($rid);
ok($r !== null,                                        'Resume::getById returns resume');
ok($r['title'] === 'Software Engineer Resume',         'Resume::getById title matches');
ok($r['first_name'] === 'John',                        'Resume::getById first_name matches');
ok(isset($r['education']),                             'Resume::getById includes education array');
ok(isset($r['experience']),                            'Resume::getById includes experience array');
ok(isset($r['skills']),                                'Resume::getById includes skills array');
ok(isset($r['projects']),                              'Resume::getById includes projects array');
ok(isset($r['certifications']),                        'Resume::getById includes certifications array');

ok($resumeModel->belongsToUser($rid, $uid),            'Resume::belongsToUser correct ownership');
ok(!$resumeModel->belongsToUser($rid, $adminId),       'Resume::belongsToUser denies wrong user');

$resumeModel->update($rid, ['title' => 'Updated Title', 'city' => 'Boston']);
$r2 = $resumeModel->getById($rid);
ok($r2['title'] === 'Updated Title', 'Resume::update changes title');
ok($r2['city'] === 'Boston',         'Resume::update changes city');

$userResumes = $resumeModel->getByUserId($uid);
ok(count($userResumes) === 1, 'Resume::getByUserId returns 1 resume');

// Education
$eid = $resumeModel->addEducation($rid, [
    'institution'    => 'MIT',
    'degree'         => 'Bachelor',
    'field_of_study' => 'Computer Science',
    'start_date'     => '2018-09-01',
    'end_date'       => '2022-05-31',
    'gpa'            => 3.8,
]);
ok($eid > 0, 'Resume::addEducation returns new ID');
$rWithEdu = $resumeModel->getById($rid);
ok(count($rWithEdu['education']) === 1,               'Education attached to resume');
ok($rWithEdu['education'][0]['institution'] === 'MIT','Education institution correct');

$resumeModel->updateEducation($eid, ['gpa' => 3.9]);
$rAfterEduUpdate = $resumeModel->getById($rid);
ok((float)$rAfterEduUpdate['education'][0]['gpa'] === 3.9, 'Resume::updateEducation changes GPA');

// Experience
$xid = $resumeModel->addExperience($rid, [
    'company'    => 'Acme Corp',
    'position'   => 'Senior Developer',
    'start_date' => '2022-06-01',
    'is_current' => 1,
]);
ok($xid > 0, 'Resume::addExperience returns new ID');
$rWithExp = $resumeModel->getById($rid);
ok(count($rWithExp['experience']) === 1,                   'Experience attached to resume');
ok($rWithExp['experience'][0]['company'] === 'Acme Corp',  'Experience company correct');

// Skills
$sid = $resumeModel->addSkill($rid, [
    'name'        => 'PHP',
    'proficiency' => 'expert',
    'category'    => 'Backend',
]);
ok($sid > 0, 'Resume::addSkill returns new ID');
$resumeModel->addSkill($rid, ['name' => 'MySQL', 'proficiency' => 'advanced']);
$rWithSkills = $resumeModel->getById($rid);
ok(count($rWithSkills['skills']) === 2, 'Two skills attached to resume');

$resumeModel->updateSkill($sid, ['proficiency' => 'intermediate']);
$rAfterSkillUpdate = $resumeModel->getById($rid);
$phpSkill = array_filter($rAfterSkillUpdate['skills'], fn($s) => $s['name'] === 'PHP');
ok(array_values($phpSkill)[0]['proficiency'] === 'intermediate', 'Resume::updateSkill changes proficiency');

// Projects
$pid = $resumeModel->addProject($rid, [
    'name'        => 'NewCareer App',
    'description' => 'Career management platform',
    'url'         => 'https://github.com/example/newcareer',
]);
ok($pid > 0, 'Resume::addProject returns new ID');

// Certifications
$cid = $resumeModel->addCertification($rid, [
    'name'       => 'AWS Solutions Architect',
    'issuer'     => 'Amazon',
    'issue_date' => '2023-01-15',
]);
ok($cid > 0, 'Resume::addCertification returns new ID');

// Full resume with all relations
$fullResume = $resumeModel->getById($rid);
ok(count($fullResume['education'])     === 1, 'Full resume has 1 education entry');
ok(count($fullResume['experience'])    === 1, 'Full resume has 1 experience entry');
ok(count($fullResume['skills'])        === 2, 'Full resume has 2 skill entries');
ok(count($fullResume['projects'])      === 1, 'Full resume has 1 project entry');
ok(count($fullResume['certifications']) === 1,'Full resume has 1 certification entry');

// Delete sub-resources
$resumeModel->deleteEducation($eid);
$resumeModel->deleteExperience($xid);
$resumeModel->deleteSkill($sid);
$resumeModel->deleteProject($pid);
$resumeModel->deleteCertification($cid);

$afterDelete = $resumeModel->getById($rid);
ok(count($afterDelete['education'])      === 0, 'Education deleted');
ok(count($afterDelete['experience'])     === 0, 'Experience deleted');
ok(count($afterDelete['skills'])         === 1, 'One skill remaining after delete');
ok(count($afterDelete['projects'])       === 0, 'Project deleted');
ok(count($afterDelete['certifications']) === 0, 'Certification deleted');

// getAll pagination
$resumeModel->create($adminId, [
    'title'      => 'Admin Resume',
    'first_name' => 'Admin',
    'last_name'  => 'User',
    'email'      => 'admin@resume.com',
]);
$allResumes = $resumeModel->getAll(1, 10);
ok(isset($allResumes['data']) && isset($allResumes['total']), 'Resume::getAll returns paginated structure');
ok($allResumes['total'] >= 2, 'Resume::getAll total >= 2');

// Delete resume
$resumeModel->delete($rid);
ok($resumeModel->getById($rid) === null, 'Resume::delete removes resume');

// ========================================================================= //
//  Summary
// ========================================================================= //
echo "\n════════════════════════════════════════════════════════════════════════\n";
echo "Results: \033[32m{$passed} passed\033[0m, \033[31m{$failed} failed\033[0m\n\n";
exit($failed > 0 ? 1 : 0);
