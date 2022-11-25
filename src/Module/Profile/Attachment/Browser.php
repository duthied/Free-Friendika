<?php

namespace Friendica\Module\Profile\Attachment;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Attach;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Browser for Attachments
 */
class Browser extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var App */
	protected $app;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, App $app, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session = $session;
		$this->app     = $app;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			$this->baseUrl->redirect();
		}

		// Needed to match the correct template in a module that uses a different theme than the user/site/default
		$theme = Strings::sanitizeFilePathItem($request['theme'] ?? '');
		if ($theme && is_file("view/theme/$theme/config.php")) {
			$this->app->setCurrentTheme($theme);
		}

		$files = Attach::selectToArray(['id', 'filename', 'filetype'], ['uid' => $this->session->getLocalUserId()]);


		$fileArray = array_map([$this, 'map_files'], $files);

		$tpl    = Renderer::getMarkupTemplate('profile/filebrowser.tpl');
		$output = Renderer::replaceMacros($tpl, [
			'$type'     => 'attachment',
			'$path'     => ['' => $this->t('Files')],
			'$folders'  => false,
			'$files'    => $fileArray,
			'$cancel'   => $this->t('Cancel'),
			'$nickname' => $this->app->getLoggedInUserNickname(),
			'$upload'   => $this->t('Upload'),
		]);

		if (empty($request['mode'])) {
			System::httpExit($output);
		}

		return $output;
	}

	protected function map_files(array $record): array
	{
		[$m1, $m2] = explode('/', $record['filetype']);
		$filetype   = file_exists(sprintf('images/icons/%s.png', $m1) ? $m1 : 'zip');

		return [
			sprintf('%s/attach/%s', $this->baseUrl, $record['id']),
			$record['filename'],
			sprintf('%s/images/icon/16/%s.png', $this->baseUrl, $filetype),
		];
	}
}
