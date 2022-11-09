<?php

namespace Friendica\Module\Search;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Friendica\Util\Proxy;
use Psr\Log\LoggerInterface;

/**
 * Search users because of their public/private tags
 */
class Tags extends BaseModule
{
	const DEFAULT_ITEMS_PER_PAGE = 80;

	/** @var Database */
	protected $database;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Database $database, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
	}

	protected function rawContent(array $request = [])
	{
		$tags     = $request['s'] ?? '';
		$perPage  = intval($request['n'] ?? self::DEFAULT_ITEMS_PER_PAGE);
		$page     = intval($request['p'] ?? 1);
		$startRec = ($page - 1) * $perPage;

		$results = [];

		if (empty($tags)) {
			System::jsonExit([
				'total'      => 0,
				'items_page' => $perPage,
				'page'       => $page,
				'results'    => $results,
			]);
		}

		$condition = [
			"`net-publish` AND MATCH(`pub_keywords`) AGAINST (?)",
			$tags
		];

		$totalCount = $this->database->count('owner-view', $condition);
		if ($totalCount === 0) {
			System::jsonExit([
				'total'      => 0,
				'items_page' => $perPage,
				'page'       => $page,
				'results'    => $results,
			]);
		}

		$searchStmt = $this->database->select('owner-view',
			['pub_keywords', 'name', 'nickname', 'uid'],
			$condition,
			['limit' => [$startRec, $perPage]]);

		while ($searchResult = $this->database->fetch($searchStmt)) {
			$results[] = [
				'name'  => $searchResult['name'],
				'url'   => $this->baseUrl . '/profile/' . $searchResult['nickname'],
				'photo' => User::getAvatarUrl($searchResult, Proxy::SIZE_THUMB),
			];
		}

		$this->database->close($searchStmt);

		System::jsonExit([
			'total'      => $totalCount,
			'items_page' => $perPage,
			'page'       => $page,
			'results'    => $results,
		]);
	}
}
