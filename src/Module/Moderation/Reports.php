<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module\Moderation;

use Friendica\App;
use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Module\BaseModeration;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Reports extends BaseModeration
{
	/** @var Database */
	private $database;

	public function __construct(Database $database, App\Page $page, App $app, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $app, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$total = $this->database->count('report');

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 10);

		$query = $this->database->p("SELECT `report`.`id`, `report`.`cid`, `report`.`comment`, `report`.`forward`, `report`.`created`, `report`.`reporter-id`,
			`report`.`category`, `report`.`rules`, `contact`.`micro`, `contact`.`name`, `contact`.`nick`, `contact`.`url`, `contact`.`addr` FROM report
			INNER JOIN `contact` ON `contact`.`id` = `report`.`cid` ORDER BY `report`.`created` DESC LIMIT ?, ?", $pager->getStart(), $pager->getItemsPerPage());

		$reports = [];
		while ($report = $this->database->fetch($query)) {
			$report['posts']   = [];
			$report['created'] = DateTimeFormat::local($report['created'], DateTimeFormat::MYSQL);

			$reports[$report['id']] = $report;
		}
		$this->database->close($query);

		$condition = ["SELECT `post-view`.`created`, `post-view`.`guid`, `post-view`.`plink`, `post-view`.`title`, `post-view`.`body`, `report-post`.`rid`
			FROM `report-post` INNER JOIN `post-view` ON `report-post`.`uri-id` = `post-view`.`uri-id`"];
		$condition = DBA::mergeConditions($condition, ['rid' => array_keys($reports)]);
		$posts     = $this->database->p(array_shift($condition), $condition);
		while ($post = $this->database->fetch($posts)) {
			if (in_array($post['rid'], array_keys($reports))) {
				$post['created'] = DateTimeFormat::local($post['created'], DateTimeFormat::MYSQL);
				$post['body']    = BBCode::toPlaintext($post['body'] ?? '');

				$reports[$post['rid']]['posts'][] = $post;
			}
		}
		$this->database->close($posts);

		$t = Renderer::getMarkupTemplate('moderation/report/overview.tpl');
		return Renderer::replaceMacros($t, [
			// strings //
			'$title'       => $this->t('Moderation'),
			'$page'        => $this->t('List of reports'),
			'$description' => $this->t('This page display reports created by our or remote users.'),
			'$no_data'     => $this->t('No report exists at this node.'),

			'$h_reports'  => $this->t('Reports'),
			'$th_reports' => [$this->t('Created'), $this->t('Photo'), $this->t('Name'), $this->t('Comment'), $this->t('Category')],

			// values //
			'$baseurl' => $this->baseUrl,

			'$reports'       => $reports,
			'$total_reports' => $this->tt('%s total report', '%s total reports', $total),
			'$paginate'      => $pager->renderFull($total),

			'$contacturl' => ['contact_url', $this->t('Profile URL'), '', $this->t('URL of the reported contact.')],
		]);
	}
}
