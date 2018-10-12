<?php
/**
 * Copyright (c) 2018 Adshares sp. z o.o.
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

namespace Adshares\Adserver\Http\Controllers\Rest;

use Adshares\Adserver\Http\Controllers\Controller;
use Adshares\Adserver\Models\Campaign;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CampaignsController extends Controller
{
    public function add(Request $request)
    {
        $this->validateRequestObject($request, 'campaign', Campaign::$rules);
        $input = $request->input('campaign');
        $input['user_id'] = Auth::user()->id;
        $campaign = Campaign::create($input);

        $campaign->save();

        $reqObj = $request->input('campaign.targeting.require');
        if (null != $reqObj) {
            foreach (array_keys($reqObj) as $key) {
                $value = $reqObj[$key];
                $campaign->campaignRequires()->create(['key' => $key, 'value' => $value]);
            }
        }

        $reqObj = $request->input('campaign.targeting.exclude');
        if (null != $reqObj) {
            foreach (array_keys($reqObj) as $key) {
                $value = $reqObj[$key];
                $campaign->campaignExcludes()->create(['key' => $key, 'value' => $value]);
            }
        }

        \response();
        return self::json([], Response::HTTP_CREATED)
            ->header('Location', route('app.campaigns.read', ['campaign' => $campaign]));
    }

    public function browse(Request $request)
    {
        // TODO check privileges
        $campaigns = Campaign::with([
            'campaignExcludes' => function ($query) {
                /* @var $query Builder */
                $query->whereNull('deleted_at');
            },
            'campaignRequires' => function ($query) {
                /* @var $query Builder */
                $query->whereNull('deleted_at');
            },
        ])->whereNull('deleted_at')->get();

        return self::json($campaigns);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Adshares\Adserver\Exceptions\JsonResponseException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function count(Request $request)
    {
        //@TODO: create function data
        $siteCount = [
            'totalBudget' => 0,
            'totalClicks' => 0,
            'totalImpressions' => 0,
            'averageCTR' => 0,
            'averageCPC' => 0,
            'totalCost' => 0,
        ];

        return self::json($siteCount, 200);
    }

    public function edit(Request $request, $campaign_id)
    {
        $this->validateRequestObject(
            $request,
            'campaign',
            array_intersect_key(
                Campaign::$rules,
                $request->input('campaign')
            )
        );

        // TODO check privileges
        $campaign = Campaign::whereNull('deleted_at')->findOrFail($campaign_id);
        $campaign->update($request->input('campaign'));

        return self::json(['message' => 'Successfully edited'], 200)
            ->header('Location', route('app.campaigns.read', ['campaign' => $campaign]));
    }

    public function delete(Request $request, $campaign_id)
    {
        // TODO check privileges
        $site = Campaign::whereNull('deleted_at')->findOrFail($campaign_id);
        $site->deleted_at = new \DateTime();
        $site->save();

        return self::json(['message' => 'Successfully deleted'], 200);
    }

    public function read(Request $request, $campaign_id)
    {
        // TODO check privileges
        $campaign = Campaign::with([
            'campaignExcludes' => function ($query) {
                /* @var $query Builder */
                $query->whereNull('deleted_at');
            },
            'campaignRequires' => function ($query) {
                /* @var $query Builder */
                $query->whereNull('deleted_at');
            },
        ])->whereNull('deleted_at')
            ->findOrFail($campaign_id);

        return self::json(['campaign' => $campaign->toArray()]);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Adshares\Adserver\Exceptions\JsonResponseException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function targeting(Request $request)
    {
        return self::json(
            json_decode(
                <<<'JSON'
[
          {
            "label": "Site",
            "key":"site",
            "children": [
              {
                "label": "Site domain",
                "key": "domain",
                "values": [
                  {"label": "coinmarketcap.com", "value": "coinmarketcap.com"},
                  {"label": "icoalert.com", "value": "icoalert.com"}
                ],
                "value_type": "string",
                "allow_input": true
              },
              {
                "label": "Inside frame",
                "key": "inframe",
                "value_type": "boolean",
                "values": [
                  {"label": "Yes", "value": "true"},
                  {"label": "No", "value": "false"}
                ],
                "allow_input": false
              },
              {
                "label": "Language",
                "key": "lang",
                "values": [
                  {"label": "Polish", "value": "pl"},
                  {"label": "English", "value": "en"},
                  {"label": "Italian", "value": "it"},
                  {"label": "Japanese", "value": "jp"}
                ],
                "value_type": "string",
                "allow_input": false
              },
              {
                "label": "Content keywords",
                "key": "keywords",
                "values": [
                  {"label": "blockchain", "value": "blockchain"},
                  {"label": "ico", "value": "ico"}
                ],
                "value_type": "string",
                "allow_input": true
              }
            ]
          },
          {
            "label": "User",
            "key":"user",
            "children": [
              {
                "label": "Age",
                "key": "age",
                "values": [
                  {"label": "18-35", "value": "18,35"},
                  {"label": "36-65", "value": "36,65"}
                ],
                "value_type": "number",
                "allow_input": true
              },
              {

                "label": "Height",
                "key": "height",
                "values": [
                  {"label": "900 or more", "value": "<900,>"},
                  {"label": "between 200 and 300", "value": "<200,300>"}
                ],
                "value_type": "number",
                "allow_input": true
              },
              {
                "label": "Interest keywords",
                "key": "keywords",
                "values": [
                  {"label": "blockchain", "value": "blockchain"},
                  {"label": "ico", "value": "ico"}
                ],
                "value_type": "string",
                "allow_input": true
              },
              {
                "label": "Language",
                "key": "lang",
                "values": [
                  {"label": "Polish", "value": "pl"},
                  {"label": "English", "value": "en"},
                  {"label": "Italian", "value": "it"},
                  {"label": "Japanese", "value": "jp"}
                ],
                "value_type": "string",
                "allow_input": false
              },
              {
                "label": "Gender",
                "key": "gender",
                "values": [
                  {"label": "Male", "value": "pl"},
                  {"label": "Female", "value": "en"}
                ],
                "value_type": "string",
                "allow_input": false
              },
              {
                "label": "Geo",
                "key":"geo",
                "children": [
                  {
                    "label": "Continent",
                    "key": "continent",
                    "values": [
                      {"label": "Africa", "value": "af"},
                      {"label": "Asia", "value": "as"},
                      {"label": "Europe", "value": "eu"},
                      {"label": "North America", "value": "na"},
                      {"label": "South America", "value": "sa"},
                      {"label": "Oceania", "value": "oc"},
                      {"label": "Antarctica", "value": "an"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                  },
                  {
                    "label": "Country",
                    "key": "country",
                    "values": [
                      {"label": "United States", "value": "us"},
                      {"label": "Poland", "value": "pl"},
                      {"label": "Spain", "value": "eu"},
                      {"label": "China", "value": "cn"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                  }
                ]
              }
            ]
          },
          {
            "label": "Device",
            "key":"device",
            "children": [
              {
                "label": "Screen size",
                "key":"screen",
                "children": [
                  {
                    "label": "Width",
                    "key": "width",
                    "values": [
                      {"label": "1200 or more", "value": "<1200,>"},
                      {"label": "between 1200 and 1800", "value": "<1200,1800>"}
                    ],
                    "value_type": "number",
                    "allow_input": true
                  },
                  {
                    "label": "Height",
                    "key": "height",
                    "values": [
                      {"label": "1200 or more", "value": "<1200,>"},
                      {"label": "between 1200 and 1800", "value": "<1200,1800>"}
                    ],
                    "value_type": "number",
                    "allow_input": true
                  }
                ]
              },
              {
                "label": "Language",
                "key": "lang",
                "values": [
                  {"label": "Polish", "value": "pl"},
                  {"label": "English", "value": "en"},
                  {"label": "Italian", "value": "it"},
                  {"label": "Japanese", "value": "jp"}
                ],
                "value_type": "string",
                "allow_input": false
              },
              {
                "label": "Browser",
                "key": "browser",
                "values": [
                  {"label": "Chrome", "value": "Chrome"},
                  {"label": "Edge", "value": "Edge"},
                  {"label": "Firefox", "value": "Firefox"}
                ],
                "value_type": "string",
                "allow_input": false
              },
              {
                "label": "Operating system",
                "key": "os",
                "values": [
                  {"label": "Linux", "value": "Linux"},
                  {"label": "Mac", "value": "Mac"},
                  {"label": "Windows", "value": "Windows"}
                ],
                "value_type": "string",
                "allow_input": false
              },
              {
                "label": "Geo",
                "key":"geo",
                "children": [
                  {
                    "label": "Continent",
                    "key": "continent",
                    "values": [
                      {"label": "Africa", "value": "af"},
                      {"label": "Asia", "value": "as"},
                      {"label": "Europe", "value": "eu"},
                      {"label": "North America", "value": "na"},
                      {"label": "South America", "value": "sa"},
                      {"label": "Oceania", "value": "oc"},
                      {"label": "Antarctica", "value": "an"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                  },
                  {
                    "label": "Country",
                    "key": "country",
                    "values": [
                      {"label": "United States", "value": "us"},
                      {"label": "Poland", "value": "pl"},
                      {"label": "Spain", "value": "eu"},
                      {"label": "China", "value": "cn"}
                    ],
                    "value_type": "string",
                    "allow_input": false
                  }
                ]
              },
              {
                "label": "Javascript support",
                "key": "js_enabled",
                "value_type": "boolean",
                "values": [
                  {"label": "Yes", "value": "true"},
                  {"label": "No", "value": "false"}
                ],
                "allow_input": false
              }
            ]
          }
        ]
JSON
            ),
            200
        );
    }
}