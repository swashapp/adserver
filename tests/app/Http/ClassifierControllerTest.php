<?php
/**
 * Copyright (c) 2018-2019 Adshares sp. z o.o.
 *
 * This file is part of AdServer
 *
 * AdServer is free software: you can redistribute and/or modify it
 * under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * AdServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AdServer. If not, see <https://www.gnu.org/licenses/>
 */

declare(strict_types = 1);

namespace Adshares\Adserver\Tests\Http;

use Adshares\Adserver\Models\Classification;
use Adshares\Adserver\Models\NetworkBanner;
use Adshares\Adserver\Models\NetworkCampaign;
use Adshares\Adserver\Models\User;
use Adshares\Adserver\Tests\TestCase;
use function factory;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ClassifierControllerTest extends TestCase
{
    use RefreshDatabase;

    private const CLASSIFICATION_LIST = '/api/classifications';

    public function testGlobalWhenThereIsNoClassifications(): void
    {
        $user = factory(User::class)->create();
        $user->is_advertiser = 1;
        $this->actingAs($user, 'api');

        factory(NetworkCampaign::class)->create(['id' => 1]);
        factory(NetworkCampaign::class)->create(['id' => 2]);
        factory(NetworkBanner::class)->create(['network_campaign_id' => 1]);
        factory(NetworkBanner::class)->create(['network_campaign_id' => 1]);
        factory(NetworkBanner::class)->create(['network_campaign_id' => 2]);
        factory(Classification::class)->create();

        $response = $this->getJson(self::CLASSIFICATION_LIST);
        $content = json_decode($response->getContent(), true);

        $this->assertCount(3, $content);

        $this->assertNull($content[0]['classifiedGlobal']);
        $this->assertNull($content[1]['classifiedGlobal']);
        $this->assertNull($content[2]['classifiedGlobal']);

        $this->assertNull($content[0]['classifiedSite']);
        $this->assertNull($content[0]['classifiedSite']);
        $this->assertNull($content[0]['classifiedSite']);
    }

    public function testGlobalWhenThereIsOnlyGlobalClassification(): void
    {
        $user = factory(User::class)->create();
        $user->is_advertiser = 1;
        $this->actingAs($user, 'api');

        factory(NetworkCampaign::class)->create(['id' => 1]);
        factory(NetworkBanner::class)->create(['id' => 1, 'network_campaign_id' => 1]);
        factory(NetworkBanner::class)->create(['id' => 2, 'network_campaign_id' => 1]);
        factory(Classification::class)->create(['banner_id' => 1, 'status' => 0, 'site_id' => null]);

        $response = $this->getJson(self::CLASSIFICATION_LIST);
        $content = json_decode($response->getContent(), true);

        $this->assertNull($content[0]['classifiedGlobal']);
        $this->assertNull($content[0]['classifiedSite']);
        $this->assertFalse($content[1]['classifiedGlobal']);
        $this->assertNull($content[1]['classifiedSite']);
    }

    public function testSiteWhenThereIsOnlySiteClassification(): void
    {
        $user = factory(User::class)->create();
        $user->is_advertiser = 1;
        $this->actingAs($user, 'api');

        factory(NetworkCampaign::class)->create(['id' => 1]);
        factory(NetworkBanner::class)->create(['id' => 1, 'network_campaign_id' => 1]);
        factory(NetworkBanner::class)->create(['id' => 2, 'network_campaign_id' => 1]);
        factory(Classification::class)->create(['banner_id' => 1, 'status' => 0, 'site_id' => 3]);

        $response = $this->getJson(self::CLASSIFICATION_LIST.'/3');
        $content = json_decode($response->getContent(), true);

        $this->assertNull($content[0]['classifiedGlobal']);
        $this->assertNull($content[0]['classifiedSite']);
        $this->assertFalse($content[1]['classifiedSite']);
        $this->assertNull($content[1]['classifiedGlobal']);
    }

    public function testSiteWhenThereIsGlobalAndSiteClassification(): void
    {
        $user = factory(User::class)->create();
        $user->is_advertiser = 1;
        $this->actingAs($user, 'api');

        factory(NetworkCampaign::class)->create(['id' => 1]);
        factory(NetworkBanner::class)->create(['id' => 1, 'network_campaign_id' => 1]);
        factory(NetworkBanner::class)->create(['id' => 2, 'network_campaign_id' => 1]);
        factory(Classification::class)->create(['banner_id' => 1, 'status' => 0, 'site_id' => 3]);
        factory(Classification::class)->create(['banner_id' => 1, 'status' => 1, 'site_id' => null]);

        $response = $this->getJson(self::CLASSIFICATION_LIST.'/3');
        $content = json_decode($response->getContent(), true);

        $this->assertNull($content[0]['classifiedGlobal']);
        $this->assertNull($content[0]['classifiedSite']);
        $this->assertTrue($content[1]['classifiedGlobal']);
        $this->assertFalse($content[1]['classifiedSite']);
    }
}
