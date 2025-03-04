<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Bootstrap;

use Closure;
use NCU\Config\Lexicon\IConfigLexicon;
use OC\Config\Lexicon\CoreConfigLexicon;
use OC\Support\CrashReport\Registry;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Services\InitialStateProvider;
use OCP\Authentication\IAlternativeLogin;
use OCP\Calendar\ICalendarProvider;
use OCP\Calendar\Resource\IBackend as IResourceBackend;
use OCP\Calendar\Room\IBackend as IRoomBackend;
use OCP\Capabilities\ICapability;
use OCP\Collaboration\Reference\IReferenceProvider;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IWidget;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Template\ICustomTemplateProvider;
use OCP\Http\WellKnown\IHandler;
use OCP\Mail\Provider\IProvider as IMailProvider;
use OCP\Notification\INotifier;
use OCP\Profile\ILinkAction;
use OCP\Search\IProvider;
use OCP\Settings\IDeclarativeSettingsForm;
use OCP\SetupCheck\ISetupCheck;
use OCP\Share\IPublicShareTemplateProvider;
use OCP\SpeechToText\ISpeechToTextProvider;
use OCP\Support\CrashReport\IReporter;
use OCP\Talk\ITalkBackend;
use OCP\Teams\ITeamResourceProvider;
use OCP\TextProcessing\IProvider as ITextProcessingProvider;
use OCP\Translation\ITranslationProvider;
use OCP\UserMigration\IMigrator as IUserMigrator;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use function array_shift;

class RegistrationContext {
	public function __construct(LoggerInterface $logger)
 {
 }

	public function for(string $appId): IRegistrationContext
 {
 }

	/**
	 * @psalm-param class-string<ICapability> $capability
	 */
	public function registerCapability(string $appId, string $capability): void
 {
 }

	/**
	 * @psalm-param class-string<IReporter> $reporterClass
	 */
	public function registerCrashReporter(string $appId, string $reporterClass): void
 {
 }

	/**
	 * @psalm-param class-string<IWidget> $panelClass
	 */
	public function registerDashboardPanel(string $appId, string $panelClass): void
 {
 }

	public function registerService(string $appId, string $name, callable $factory, bool $shared = true): void
 {
 }

	public function registerServiceAlias(string $appId, string $alias, string $target): void
 {
 }

	public function registerParameter(string $appId, string $name, $value): void
 {
 }

	public function registerEventListener(string $appId, string $event, string $listener, int $priority = 0): void
 {
 }

	/**
	 * @psalm-param class-string<Middleware> $class
	 */
	public function registerMiddleware(string $appId, string $class, bool $global): void
 {
 }

	public function registerSearchProvider(string $appId, string $class)
 {
 }

	public function registerAlternativeLogin(string $appId, string $class): void
 {
 }

	public function registerInitialState(string $appId, string $class): void
 {
 }

	public function registerWellKnown(string $appId, string $class): void
 {
 }

	public function registerSpeechToTextProvider(string $appId, string $class): void
 {
 }

	public function registerTextProcessingProvider(string $appId, string $class): void
 {
 }

	public function registerTextToImageProvider(string $appId, string $class): void
 {
 }

	public function registerTemplateProvider(string $appId, string $class): void
 {
 }

	public function registerTranslationProvider(string $appId, string $class): void
 {
 }

	public function registerNotifierService(string $appId, string $class): void
 {
 }

	public function registerTwoFactorProvider(string $appId, string $class): void
 {
 }

	public function registerPreviewProvider(string $appId, string $class, string $mimeTypeRegex): void
 {
 }

	public function registerCalendarProvider(string $appId, string $class): void
 {
 }

	public function registerReferenceProvider(string $appId, string $class): void
 {
 }

	/**
	 * @psalm-param class-string<ILinkAction> $actionClass
	 */
	public function registerProfileLinkAction(string $appId, string $actionClass): void
 {
 }

	/**
	 * @psalm-param class-string<ITalkBackend> $backend
	 */
	public function registerTalkBackend(string $appId, string $backend)
 {
 }

	public function registerCalendarResourceBackend(string $appId, string $class)
 {
 }

	public function registerCalendarRoomBackend(string $appId, string $class)
 {
 }

	/**
	 * @psalm-param class-string<ITeamResourceProvider> $class
	 */
	public function registerTeamResourceProvider(string $appId, string $class)
 {
 }

	/**
	 * @psalm-param class-string<IUserMigrator> $migratorClass
	 */
	public function registerUserMigrator(string $appId, string $migratorClass): void
 {
 }

	public function registerSensitiveMethods(string $appId, string $class, array $methods): void
 {
 }

	public function registerPublicShareTemplateProvider(string $appId, string $class): void
 {
 }

	/**
	 * @psalm-param class-string<ISetupCheck> $setupCheckClass
	 */
	public function registerSetupCheck(string $appId, string $setupCheckClass): void
 {
 }

	/**
	 * @psalm-param class-string<IDeclarativeSettingsForm> $declarativeSettingsClass
	 */
	public function registerDeclarativeSettings(string $appId, string $declarativeSettingsClass): void
 {
 }

	/**
	 * @psalm-param class-string<\OCP\TaskProcessing\IProvider> $declarativeSettingsClass
	 */
	public function registerTaskProcessingProvider(string $appId, string $taskProcessingProviderClass): void
 {
 }

	/**
	 * @psalm-param class-string<\OCP\TaskProcessing\ITaskType> $declarativeSettingsClass
	 */
	public function registerTaskProcessingTaskType(string $appId, string $taskProcessingTaskTypeClass)
 {
 }

	/**
	 * @psalm-param class-string<\OCP\Files\Conversion\IConversionProvider> $class
	 */
	public function registerFileConversionProvider(string $appId, string $class): void
 {
 }

	/**
	 * @psalm-param class-string<IMailProvider> $migratorClass
	 */
	public function registerMailProvider(string $appId, string $class): void
 {
 }

	/**
	 * @psalm-param class-string<IConfigLexicon> $configLexiconClass
	 */
	public function registerConfigLexicon(string $appId, string $configLexiconClass): void
 {
 }

	/**
	 * @param App[] $apps
	 */
	public function delegateCapabilityRegistrations(array $apps): void
 {
 }

	/**
	 * @param App[] $apps
	 */
	public function delegateCrashReporterRegistrations(array $apps, Registry $registry): void
 {
 }

	public function delegateDashboardPanelRegistrations(IManager $dashboardManager): void
 {
 }

	public function delegateEventListenerRegistrations(IEventDispatcher $eventDispatcher): void
 {
 }

	/**
	 * @param App[] $apps
	 */
	public function delegateContainerRegistrations(array $apps): void
 {
 }

	/**
	 * @return MiddlewareRegistration[]
	 */
	public function getMiddlewareRegistrations(): array
 {
 }

	/**
	 * @return ServiceRegistration<IProvider>[]
	 */
	public function getSearchProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<IAlternativeLogin>[]
	 */
	public function getAlternativeLogins(): array
 {
 }

	/**
	 * @return ServiceRegistration<InitialStateProvider>[]
	 */
	public function getInitialStates(): array
 {
 }

	/**
	 * @return ServiceRegistration<IHandler>[]
	 */
	public function getWellKnownHandlers(): array
 {
 }

	/**
	 * @return ServiceRegistration<ISpeechToTextProvider>[]
	 */
	public function getSpeechToTextProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<ITextProcessingProvider>[]
	 */
	public function getTextProcessingProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<\OCP\TextToImage\IProvider>[]
	 */
	public function getTextToImageProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<ICustomTemplateProvider>[]
	 */
	public function getTemplateProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<ITranslationProvider>[]
	 */
	public function getTranslationProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<INotifier>[]
	 */
	public function getNotifierServices(): array
 {
 }

	/**
	 * @return ServiceRegistration<\OCP\Authentication\TwoFactorAuth\IProvider>[]
	 */
	public function getTwoFactorProviders(): array
 {
 }

	/**
	 * @return PreviewProviderRegistration[]
	 */
	public function getPreviewProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<ICalendarProvider>[]
	 */
	public function getCalendarProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<IReferenceProvider>[]
	 */
	public function getReferenceProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<ILinkAction>[]
	 */
	public function getProfileLinkActions(): array
 {
 }

	/**
	 * @return ServiceRegistration|null
	 * @psalm-return ServiceRegistration<ITalkBackend>|null
	 */
	public function getTalkBackendRegistration(): ?ServiceRegistration
 {
 }

	/**
	 * @return ServiceRegistration[]
	 * @psalm-return ServiceRegistration<IResourceBackend>[]
	 */
	public function getCalendarResourceBackendRegistrations(): array
 {
 }

	/**
	 * @return ServiceRegistration[]
	 * @psalm-return ServiceRegistration<IRoomBackend>[]
	 */
	public function getCalendarRoomBackendRegistrations(): array
 {
 }

	/**
	 * @return ServiceRegistration<IUserMigrator>[]
	 */
	public function getUserMigrators(): array
 {
 }

	/**
	 * @return ParameterRegistration[]
	 */
	public function getSensitiveMethods(): array
 {
 }

	/**
	 * @return ServiceRegistration<IPublicShareTemplateProvider>[]
	 */
	public function getPublicShareTemplateProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<ISetupCheck>[]
	 */
	public function getSetupChecks(): array
 {
 }

	/**
	 * @return ServiceRegistration<ITeamResourceProvider>[]
	 */
	public function getTeamResourceProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<IDeclarativeSettingsForm>[]
	 */
	public function getDeclarativeSettings(): array
 {
 }

	/**
	 * @return ServiceRegistration<\OCP\TaskProcessing\IProvider>[]
	 */
	public function getTaskProcessingProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<\OCP\TaskProcessing\ITaskType>[]
	 */
	public function getTaskProcessingTaskTypes(): array
 {
 }

	/**
	 * @return ServiceRegistration<\OCP\Files\Conversion\IConversionProvider>[]
	 */
	public function getFileConversionProviders(): array
 {
 }

	/**
	 * @return ServiceRegistration<IMailProvider>[]
	 */
	public function getMailProviders(): array
 {
 }

	/**
	 * returns IConfigLexicon registered by the app.
	 * null if none registered.
	 *
	 * @param string $appId
	 *
	 * @return IConfigLexicon|null
	 */
	public function getConfigLexicon(string $appId): ?IConfigLexicon
 {
 }
}
