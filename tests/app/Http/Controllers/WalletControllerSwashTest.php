<?php
/**
 * Copyright (c) 2018-2022 Adshares sp. z o.o.
 *
 * This file is part of AdServer
 *
 * AdServer is free software: you can redistribute and/or modify it
 * under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AdServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AdServer. If not, see <https://www.gnu.org/licenses/>
 */

// phpcs:ignoreFile PHPCompatibility.Miscellaneous.ValidIntegers.HexNumericStringFound

declare(strict_types=1);

namespace Adshares\Adserver\Tests\Http\Controllers;

use Adshares\Adserver\Models\User;
use Adshares\Adserver\Models\UserLedgerEntry;
use Adshares\Adserver\Tests\TestCase;
use Adshares\Common\Domain\ValueObject\WalletAddress;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;

class WalletControllerSwashTest extends TestCase
{

    public function testWithdrawSwash(): void
    {
        Mail::fake();
        Queue::fake();
        $this->createSwashEntries();

        $user = factory(User::class)->create([
            'email_confirmed_at' => now(),
            'admin_confirmed_at' => now(),
            'email' => null,
            'wallet_address' => new WalletAddress(WalletAddress::NETWORK_ADS, '0001-00000001-8B4E')
        ]);
        $user->is_admin = true;
        $user->saveOrFail();
        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/withdraw-swash');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['to' => config('app.swash_bsc_address'), 'total' => 151924038]);
        $json = $response->decodeResponseJson();
        $userLedgerEntry = UserLedgerEntry::getFirstRecordByBatchId($json['batch']);
        $this->assertEquals(0, UserLedgerEntry::getWalletBalanceForAllUsers());
        $this->assertNotNull($userLedgerEntry);
        $this->assertEquals(UserLedgerEntry::STATUS_PENDING, $userLedgerEntry->status);
    }

    public function testWithdrawSwashInfoSuccess(): void
    {
        Mail::fake();
        Queue::fake();
        $this->createSwashEntries();

        $user = factory(User::class)->create([
            'email_confirmed_at' => now(),
            'admin_confirmed_at' => now(),
            'email' => null,
            'wallet_address' => new WalletAddress(WalletAddress::NETWORK_ADS, '0001-00000001-8B4E')
        ]);
        $user->is_admin = true;
        $user->saveOrFail();
        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/withdraw-swash');
        $response->assertStatus(Response::HTTP_OK);
        $json = $response->decodeResponseJson();
        $this->actingAs($user, 'api');
        $response = $this->getJson('/api/withdraw-swash-info');
        $response->assertStatus(Response::HTTP_BAD_REQUEST);

        $response = $this->getJson('/api/withdraw-swash-info?batch='. $json['batch']);
        $response->assertStatus(Response::HTTP_OK);
        $json2 = $response->decodeResponseJson();
        $this->assertEquals(1, $json2['code']);
        $this->assertEquals('pending', $json2['status']);
        $txid = '1234';
        UserLedgerEntry::acceptAllRecordsInBatch($json['batch'], $txid);

        $response = $this->getJson('/api/withdraw-swash-info?batch='. $json['batch']);
        $response->assertStatus(Response::HTTP_OK);
        $json2 = $response->decodeResponseJson();
        $this->assertEquals(0, $json2['code']);
        $this->assertEquals($txid, $json2['txid']);
        $this->assertEquals('accepted', $json2['status']);
        $userLedgerEntry = UserLedgerEntry::getFirstRecordByBatchId($json['batch']);
        foreach ($json2['shares'] as $item) {
            if($item['uid'] === $userLedgerEntry->user->name){
                $this->assertEquals($item['ads'], $userLedgerEntry->amount * -1);
            }
        }
        $response = $this->postJson('/api/withdraw-swash');
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testWithdrawSwashInfoFail(): void
    {
        Mail::fake();
        Queue::fake();
        $this->createSwashEntries();

        $user = factory(User::class)->create([
            'email_confirmed_at' => now(),
            'admin_confirmed_at' => now(),
            'email' => null,
            'wallet_address' => new WalletAddress(WalletAddress::NETWORK_ADS, '0001-00000001-8B4E')
        ]);
        $user->is_admin = true;
        $user->saveOrFail();
        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/withdraw-swash');
        $response->assertStatus(Response::HTTP_OK);
        $json = $response->decodeResponseJson();
        
        $response = $this->getJson('/api/withdraw-swash-info?batch='. $json['batch']);
        $response->assertStatus(Response::HTTP_OK);
        $json2 = $response->decodeResponseJson();
        $this->assertEquals(1, $json2['code']);
        $this->assertEquals('pending', $json2['status']);
        UserLedgerEntry::failAllRecordsInBatch($json['batch'], UserLedgerEntry::STATUS_NET_ERROR);

        $response = $this->getJson('/api/withdraw-swash-info?batch='. $json['batch']);
        $response->assertStatus(Response::HTTP_OK);
        $json2 = $response->decodeResponseJson();
        $this->assertEquals(UserLedgerEntry::STATUS_NET_ERROR, $json2['code']);
        $this->assertEquals('failed', $json2['status']);
        $response = $this->postJson('/api/withdraw-swash');
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function generateUserIncome(int $userId, int $amount): void
    {
        $dateString = '2018-10-24 15:00:49';

        $ul = new UserLedgerEntry();
        $ul->user_id = $userId;
        $ul->amount = $amount;
        $ul->address_from = '0001-00000000-XXXX';
        $ul->address_to = '0001-00000000-XXXX';
        $ul->txid = '0001:0000000A:0001';
        $ul->type = UserLedgerEntry::TYPE_DEPOSIT;
        $ul->setCreatedAt($dateString);
        $ul->setUpdatedAt($dateString);
        $ul->save();
    }

    private function createSwashEntries(): void
    {
        $entries = [
            ['0x9552D752001721d43d8F04AC4FDfb7aE27800001', 100000000],
            ['0x9552D752001721d43d8F04AC4FDfb7aE27800002', 40000000],
            ['0x9552D752001721d43d8F04AC4FDfb7aE27800003', 1000000],
            ['0x9552D752001721d43d8F04AC4FDfb7aE27800004', 0],
            ['0x9552D752001721d43d8F04AC4FDfb7aE27800005', 11000000]
        ];    
        foreach ($entries as $entry) {
            $user = factory(User::class)->create();
            $user->swash_wallet_address = $entry[0];
            $user->save();
            $this->generateUserIncome($user->id, $entry[1]);
        }
    }
}
