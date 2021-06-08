<?php

/** @var CirclesManager $circlesManager */

use OCA\Circles\CirclesManager;
use OCA\Circles\Model\Member;

$circlesManager = \OC::$server->get(CirclesManager::class);

$circlesQueryHelper = $circlesManager->getQueryHelper();

$qb = $circlesQueryHelper->getQueryBuilder();
$qb->select(
	'test.id',
	'test.shared_to',
	'test.data'
)
   ->from('circles_test', 'test');


$federatedUser = $circlesManager->getFederatedUser('test9', Member::TYPE_USER);
$circlesQueryHelper->limitToInheritedMembers('test', 'shared_to', $federatedUser, true);
$circlesQueryHelper->addCircleDetails('test', 'shared_to');

$items = [];
$cursor = $qb->execute();
while ($row = $cursor->fetch()) {
	try {
		$items[] = [
			'id'     => $row['id'],
			'data'   => $row['data'],
			'circle' => $circlesQueryHelper->extractCircle($row)
		];
	} catch (Exception $e) {
	}
}
$cursor->closeCursor();

echo json_encode($items, JSON_PRETTY_PRINT);
