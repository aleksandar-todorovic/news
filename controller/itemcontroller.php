<?php

/**
* ownCloud - News
*
* @author Alessandro Cosentino
* @author Bernhard Posselt
* @copyright 2012 Alessandro Cosentino cosenal@gmail.com
* @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\News\Controller;

use \OCA\AppFramework\Controller\Controller;
use \OCA\AppFramework\Core\API;
use \OCA\AppFramework\Http\Request;

use \OCA\News\BusinessLayer\BusinessLayerException;
use \OCA\News\BusinessLayer\ItemBusinessLayer;
use \OCA\News\BusinessLayer\FeedBusinessLayer;


class ItemController extends Controller {

	private $itemBusinessLayer;
	private $feedBusinessLayer;

	public function __construct(API $api, Request $request, 
		                        ItemBusinessLayer $itemBusinessLayer,
		                        FeedBusinessLayer $feedBusinessLayer){
		parent::__construct($api, $request);
		$this->itemBusinessLayer = $itemBusinessLayer;
		$this->feedBusinessLayer = $feedBusinessLayer;
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function items(){
		$userId = $this->api->getUserId();
		$showAll = $this->api->getUserValue('showAll') === '1';

		$limit = $this->params('limit');
		$type = (int) $this->params('type');
		$id = (int) $this->params('id');
		$offset = (int) $this->params('offset', 0);

		$this->api->setUserValue('lastViewedFeedId', $id);
		$this->api->setUserValue('lastViewedFeedType', $type);

		$params = array();

		try {

			// the offset is 0 if the user clicks on a new feed
			// we need to pass the newest feeds to not let the unread count get 
			// out of sync
			if($offset === 0) {
				$params['newestItemId'] = 
					$this->itemBusinessLayer->getNewestItemId($userId);
				$params['feeds'] = $this->feedBusinessLayer->findAll($userId);
				$params['starred'] = $this->itemBusinessLayer->starredCount($userId);
			}
						
			$params['items'] = $this->itemBusinessLayer->findAll($id, $type, $limit, 
				                                       $offset, $showAll, $userId);
		// this gets thrown if there are no items
		// in that case just return an empty array
		} catch(BusinessLayerException $ex) {}

		return $this->renderJSON($params);
	}


	private function setStarred($isStarred){
		$userId = $this->api->getUserId();
		$feedId = (int) $this->params('feedId');
		$guidHash = $this->params('guidHash');

		$this->itemBusinessLayer->star($feedId, $guidHash, $isStarred, $userId);
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function star(){
		$this->setStarred(true);

		return $this->renderJSON();
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function unstar(){
		$this->setStarred(false);

		return $this->renderJSON();
	}


	private function setRead($isRead){
		$userId = $this->api->getUserId();
		$itemId = (int) $this->params('itemId');

		$this->itemBusinessLayer->read($itemId, $isRead, $userId);
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function read(){
		$this->setRead(true);

		return $this->renderJSON();
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function unread(){
		$this->setRead(false);

		return $this->renderJSON();
	}


	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function readFeed(){
		$userId = $this->api->getUserId();
		$feedId = (int) $this->params('feedId');
		$highestItemId = (int) $this->params('highestItemId');

		$this->itemBusinessLayer->readFeed($feedId, $highestItemId, $userId);

		$params = array(
			'feeds' => array(
				array(
					'id' => $feedId,
					'unreadCount' => 0
				)
			)
		);
		return $this->renderJSON($params);
	}


}