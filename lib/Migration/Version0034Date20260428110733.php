<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Circles\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

#[AddIndex('circles_event', IndexType::INDEX, 'lighten the cleaning of the table')]
class Version0034Date20260428110733 extends SimpleMigrationStep {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        try {
            $table = $schema->getTable('circles_event');
            if ($table->hasIndex('circles_event_sc_cr')) {
                return null;
            }

            $table->addIndex(['status', 'creation'], 'circles_event_sc_cr');
        } catch (SchemaException $e) {
            $this->logger->warning('Could not add index to circles_event', ['exception' => $e]);
            return null;
        }

        return $schema;
    }
}
