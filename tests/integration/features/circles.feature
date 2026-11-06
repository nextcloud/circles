# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

Feature: Circles OCS API

  Background:
    Given user "participant1" exists
    And user "participant2" exists

  Scenario: A fresh user can list their circles
    When user "participant1" sends an OCS "GET" request to "/apps/circles/circles"
    Then the last response should have a "200" status code
    And the OCS meta status should be "ok"
    And the response should contain a list of circles

  Scenario: Circle members list reflects the circle composition
    Given user "participant1" creates circle "Team One" as "c1"
    And user "participant1" adds user "participant2" to circle "c1"
    When user "participant1" sends an OCS "GET" request to "/apps/circles/circles"
    Then the last response should have a "200" status code
    And the circles list should contain circle "c1"
    When user "participant1" fetches members of circle "c1"
    Then the last response should have a "200" status code
    And the OCS meta status should be "ok"
    And the response data should contain "2" entries
    And the members should contain user "participant1" with level "9"
    And the members should contain user "participant2" with level "1"
