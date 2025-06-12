<?php

use Tests\Support\FunctionalTester;

class FormCest
{
    private string $username;

    public function _before(FunctionalTester $I)
    {
        $jsonPath = codecept_root_dir() . 'tests/test-config.json';
            $json = file_get_contents($jsonPath);
            $data = json_decode($json, true);

            $this->username = $data['username'] ?? 'default_user';
    }

    public function seeCrudFormElements(FunctionalTester $I)
    {
        $I->amOnPage('/run-test/' . $this->username);
        $I->seeInTitle('Manajemen Pengguna');
        $I->see('Manajemen Pengguna');

        // POST
        $I->see('Tambah User (POST)', 'h2');
        $I->seeElement('form[action="post.php"]');
        $I->see('Nama:', 'label');
        $I->seeElement('input[name="name"][type="text"][required]');
        $I->see('Email:', 'label');
        $I->seeElement('input[name="email"][type="email"][required]');
        $I->see('Simpan', 'button[type="submit"]');

        // GET
        $I->see('Cari User (GET)', 'h2');
        $I->seeElement('form[action="get.php"]');
        $I->see('Lihat semua data:');
        $I->seeElement('a[href="get.php"]');
        $I->see('ID:', 'label');
        $I->seeElement('input[name="id"][type="number"][required]');
        $I->see('Cari', 'button[type="submit"]');

        // PUT
        $I->see('Edit User (PUT)', 'h2');
        $I->seeElement('form[action="put.php"]');
        $I->see('ID:', 'label');
        $I->see('Nama Baru:', 'label');
        $I->see('Email Baru:', 'label');
        $I->seeElement('input[name="id"][type="number"][required]');
        $I->seeElement('input[name="name"][type="text"][required]');
        $I->seeElement('input[name="email"][type="email"][required]');
        $I->see('Update', 'button[type="submit"]');

        // DELETE
        $I->see('Hapus User (DELETE)', 'h2');
        $I->seeElement('form[action="delete.php"]');
        $I->see('ID:', 'label');
        $I->seeElement('input[name="id"][type="number"][required]');
        $I->see('Hapus', 'button[type="submit"]');
    }

    public function submitPostForm(FunctionalTester $I)
    {
        $I->amOnPage('/run-test/' . $this->username);
        $I->submitForm('form[action="post.php"]', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        $I->seeInCurrentUrl('post.php');
        $I->see('success');

        $pageSource = $I->grabPageSource();
        $data = json_decode($pageSource, true);

        if (!$data || !isset($data['data']['id'])) {
            $I->fail('Response tidak valid atau tidak mengandung ID user');
            return;
        }

        file_put_contents(codecept_output_dir() . 'test_user_id.json', json_encode([
            'id' => $data['data']['id']
        ]));
    }

    public function submitPutForm(FunctionalTester $I)
    {
        $user = json_decode(file_get_contents(codecept_output_dir() . 'test_user_id.json'), true);
        $id = $user['id'];

        $I->amOnPage('/run-test/' . $this->username);
        $I->submitForm('form[action="put.php"]', [
            'id' => $id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
        $I->seeInCurrentUrl('put.php');
        $I->see('success');
    }

    public function submitDeleteForm(FunctionalTester $I)
    {
        $user = json_decode(file_get_contents(codecept_output_dir() . 'test_user_id.json'), true);
        $id = $user['id'];

        $I->amOnPage('/run-test/' . $this->username);
        $I->submitForm('form[action="delete.php"]', [
            'id' => $id
        ]);
        $I->seeInCurrentUrl('delete.php');
        $I->see('success');
    }
}
