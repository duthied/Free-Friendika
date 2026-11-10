<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util\Database;

/**
 * Test database that makes full text search queries work within the open test transaction
 *
 * InnoDB only indexes full text columns at commit time. Since the test harness rolls
 * back every transaction, MATCH ... AGAINST queries would never see rows inserted
 * during a test. This database rewrites the full text condition into an equivalent
 * LIKE condition, which is visible within the open transaction.
 */
class StaticDatabaseWithFullTextSearch extends StaticDatabase
{
	private const FULLTEXT_SEARCH_TABLES = ['post-searchindex', 'post-engagement'];

	/**
	 * @inheritDoc
	 */
	public function select(string $table, array $fields = [], array $condition = [], array $params = [])
	{
		$this->rewriteFullTextCondition($table, $condition);

		return parent::select($table, $fields, $condition, $params);
	}

	/**
	 * Rewrite a full text search condition into an equivalent LIKE condition
	 *
	 * @param string $table     The table to search
	 * @param array  $condition The collapsed condition array, modified in place
	 */
	private function rewriteFullTextCondition(string $table, array &$condition): void
	{
		if (!in_array($table, self::FULLTEXT_SEARCH_TABLES, true)
			|| !isset($condition[0])
			|| !is_string($condition[0])
			|| !array_key_exists(1, $condition)) {
			return;
		}

		$fullTextCondition = 'MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE)';
		if (!str_contains($condition[0], $fullTextCondition)) {
			return;
		}

		$condition[0] = str_replace($fullTextCondition, '`searchtext` LIKE ?', $condition[0]);
		$condition[1] = '%' . $condition[1] . '%';
	}
}
