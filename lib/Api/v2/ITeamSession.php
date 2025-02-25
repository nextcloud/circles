<?php
/*
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Api\v2;

use OCA\Circles\Model\TeamEntity;
use OCP\IUser;

interface ITeamSession {
	public function sessionAsCurrentUser(): self;
	public function sessionAsUser(IUser $user): self;
	public function sessionAsLocalUser(string $userId): self;
	public function sessionAsApp(string $appId): self;
	public function sessionAsSuperAdmin(): self;
	public function sessionAsEntity(TeamEntity $entity): self;
	public function hasEntity(): bool;
	public function getEntity(): TeamEntity;
	public function performTeamOperation(): ITeamOperation;
	public function performTeamMemberOperation(): ITeamMemberOperation;
}
