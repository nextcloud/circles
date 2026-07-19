const appName = "teams";
const appVersion = "35.0.0-dev.0";
import { l as getLoggerBuilder, n as getGettextBuilder, s as spawnDialog, d as defineComponent, p as onMounted, q as useTemplateRef, v as ref, w as computed, x as nextTick, o as openBlock, y as createBlock, z as withCtx, f as createBaseVNode, h as toDisplayString, u as unref, i as createVNode, N as NcDialog, A as isAxiosError, B as generateUrl, c as cancelableClient, C as loadState, D as watchDebounced, g as createTextVNode, E as translate, F as withDirectives, G as vShow, H as generateOcsUrl, I as showError, J as logger$1, K as showSuccess, L as _export_sfc$1, M as createApp } from "./logger-BmumIVPY.chunk.mjs";
import { N as NcPasswordField, a as NcCheckboxRadioSwitch, _ as _sfc_main$2 } from "./NcCheckboxRadioSwitch-BVTMQSAg-CuDDI3i6.chunk.mjs";
import { N as NcSettingsSection } from "./NcSettingsSection-DmfxX2se-NyQBJsrf.chunk.mjs";
/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: MIT
 */
var PwdConfirmationMode = /* @__PURE__ */ ((PwdConfirmationMode2) => {
  PwdConfirmationMode2["Lax"] = "lax";
  PwdConfirmationMode2["Strict"] = "strict";
  return PwdConfirmationMode2;
})(PwdConfirmationMode || {});
/*!
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: MIT
 */
const PAGE_LOAD_TIME = Date.now();
function isPasswordConfirmationRequired(mode) {
  if (!window.backendAllowsPasswordConfirmation) {
    return false;
  }
  if (mode === PwdConfirmationMode.Strict) {
    return true;
  }
  const serverTimeDiff = PAGE_LOAD_TIME - window.nc_pageLoad * 1e3;
  const timeSinceLogin = Date.now() - (serverTimeDiff + window.nc_lastLogin * 1e3);
  return timeSinceLogin > 30 * 60 * 1e3;
}
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: CC0-1.0
 */
const logger = getLoggerBuilder().setApp("@nextcloud/password-confirmation").detectLogLevel().build();
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: MIT
 */
const [NC_MAJOR_VERSION] = window._oc_config?.version.split(".").map(Number) ?? [];
function isConfirmationError(error) {
  if (!isAxiosError(error) || !error.response) {
    return false;
  }
  const hasConfirmationHeader = error.response.headers?.["x-nextcloud-password-confirmation"] === "true";
  if (NC_MAJOR_VERSION < 32) {
    logger.debug("Handle legacy confirmation error based on status code", { status: error.response.status });
    return error.response.status === 403;
  }
  logger.debug("Handle modern confirmation error based on header", { hasConfirmationHeader });
  return hasConfirmationHeader;
}
/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: MIT
 */
const gtBuilder = getGettextBuilder().detectLanguage();
[{ "locale": "ar", "translations": [{ "msgid": "Checking password …", "msgstr": ["يتم التحقق من كلمة المرور..."] }, { "msgid": "Confirm", "msgstr": ["تأكيد"] }, { "msgid": "Confirm your password", "msgstr": ["تأكيد كلمة المرور"] }, { "msgid": "Password", "msgstr": ["كلمة المرور"] }, { "msgid": "Please enter your password", "msgstr": ["يرجى إدخال كلمة المرور الخاصة بك"] }, { "msgid": "This action needs authentication", "msgstr": ["هذا الإجراء يتطلب التحقق من الهوية"] }, { "msgid": "Wrong password", "msgstr": ["كلمة المرور غير صحيحة"] }] }, { "locale": "ast", "translations": [{ "msgid": "Checking password …", "msgstr": ["Comprobando la contraseña…"] }, { "msgid": "Confirm", "msgstr": ["Confirmación"] }, { "msgid": "Confirm your password", "msgstr": ["Confirma la contraseña"] }, { "msgid": "Password", "msgstr": ["Contraseña"] }, { "msgid": "Please enter your password", "msgstr": ["Introduz la contraseña"] }, { "msgid": "This action needs authentication", "msgstr": ["Esta aición precisa l'autenticación"] }, { "msgid": "Wrong password", "msgstr": ["La contraseña ye incorreuta"] }] }, { "locale": "az", "translations": [{ "msgid": "Confirm", "msgstr": ["Təsdiq edin"] }, { "msgid": "Confirm your password", "msgstr": ["Parolunuzu təsdiq edin"] }, { "msgid": "Password", "msgstr": ["Parol"] }, { "msgid": "Password confirmation dialog already mounted", "msgstr": ["Parolun təsdiqi dialoqu artıq quraşdırılıb"] }, { "msgid": "This action needs authentication", "msgstr": ["Bu əməliyyat autentifikasiya tələb edir"] }, { "msgid": "Wrong password", "msgstr": ["Səhv parol"] }] }, { "locale": "be", "translations": [{ "msgid": "Checking password …", "msgstr": ["Праверка пароля…"] }, { "msgid": "Confirm", "msgstr": ["Пацвердзіць"] }, { "msgid": "Confirm your password", "msgstr": ["Пацвердзіць пароль"] }, { "msgid": "Password", "msgstr": ["Пароль"] }, { "msgid": "Please enter your password", "msgstr": ["Калі ласка, увядзіце ваш пароль"] }, { "msgid": "This action needs authentication", "msgstr": ["Гэта дзеянне патрабуе аўтэнтыфікацыі"] }, { "msgid": "Wrong password", "msgstr": ["Памылковы пароль"] }] }, { "locale": "ca", "translations": [{ "msgid": "Checking password …", "msgstr": ["S'està comprovant la contrasenya …"] }, { "msgid": "Confirm", "msgstr": ["Confirma"] }, { "msgid": "Confirm your password", "msgstr": ["Confirmeu la vostra contrasenya"] }, { "msgid": "Password", "msgstr": ["Contrasenya"] }, { "msgid": "Please enter your password", "msgstr": ["Introduïu la vostra contrasenya"] }, { "msgid": "This action needs authentication", "msgstr": ["Aquesta acció necessita autenticació"] }, { "msgid": "Wrong password", "msgstr": ["Contrasenya incorrecta"] }] }, { "locale": "cs_CZ", "translations": [{ "msgid": "Authentication required", "msgstr": ["Vyžadováno ověření se"] }, { "msgid": "Checking password …", "msgstr": ["Ověřování hesla…"] }, { "msgid": "Confirm", "msgstr": ["Potvrdit"] }, { "msgid": "Password", "msgstr": ["Heslo"] }, { "msgid": "Please enter your password", "msgstr": ["Zadejte heslo"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Tato akce vyžaduje ověření se – potvrďte ji zadáním svého hesla."] }, { "msgid": "Wrong password", "msgstr": ["Nesprávné heslo"] }] }, { "locale": "da", "translations": [{ "msgid": "Authentication required", "msgstr": ["Bekræft din identitet"] }, { "msgid": "Checking password …", "msgstr": ["Kontrollerer adgangskode …"] }, { "msgid": "Confirm", "msgstr": ["Bekræft"] }, { "msgid": "Password", "msgstr": ["Adgangskode"] }, { "msgid": "Please enter your password", "msgstr": ["Indtast venligst dit kodeord"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Denne handling kræver godkendelse. Indtast din adgangskode for at bekræfte."] }, { "msgid": "Wrong password", "msgstr": ["forkert Adgangskode"] }] }, { "locale": "de", "translations": [{ "msgid": "Authentication required", "msgstr": ["Authentifizierung erforderlich"] }, { "msgid": "Checking password …", "msgstr": ["Passwort prüfen  …"] }, { "msgid": "Confirm", "msgstr": ["Bestätigen"] }, { "msgid": "Password", "msgstr": ["Passwort"] }, { "msgid": "Please enter your password", "msgstr": ["Bitte gib dein Passwort ein"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Für diese Aktion ist eine Authentifizierung erforderlich. Bitte bestätige diese durch Eingabe deines Passworts."] }, { "msgid": "Wrong password", "msgstr": ["Falsches Passwort"] }] }, { "locale": "de_DE", "translations": [{ "msgid": "Authentication required", "msgstr": ["Authentifizierung erforderlich"] }, { "msgid": "Checking password …", "msgstr": ["Passwort prüfen …"] }, { "msgid": "Confirm", "msgstr": ["Bestätigen"] }, { "msgid": "Password", "msgstr": ["Passwort"] }, { "msgid": "Please enter your password", "msgstr": ["Bitte geben Sie Ihr Passwort ein"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Für diese Aktion ist eine Authentifizierung erforderlich. Bitte bestätigen Sie diese durch Eingabe Ihres Passworts."] }, { "msgid": "Wrong password", "msgstr": ["Falsches Passwort"] }] }, { "locale": "el", "translations": [{ "msgid": "Authentication required", "msgstr": ["Απαιτείται πιστοποίηση"] }, { "msgid": "Checking password …", "msgstr": ["Έλεγχος κωδικού πρόσβασης …"] }, { "msgid": "Confirm", "msgstr": ["Επιβεβαίωση"] }, { "msgid": "Password", "msgstr": ["Συνθηματικό"] }, { "msgid": "Please enter your password", "msgstr": ["Παρακαλώ εισάγετε το συνθηματικό σας"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Αυτή η ενέργεια απαιτεί πιστοποίηση. Επιβεβαιώστε την εισάγοντας τον κωδικό πρόσβασής σας."] }, { "msgid": "Wrong password", "msgstr": ["Λάθος συνθηματικό"] }] }, { "locale": "en_GB", "translations": [{ "msgid": "Authentication required", "msgstr": ["Authentication required"] }, { "msgid": "Checking password …", "msgstr": ["Checking password …"] }, { "msgid": "Confirm", "msgstr": ["Confirm"] }, { "msgid": "Password", "msgstr": ["Password"] }, { "msgid": "Please enter your password", "msgstr": ["Please enter your password"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["This action needs authentication, please confirm it by entering your password."] }, { "msgid": "Wrong password", "msgstr": ["Wrong password"] }] }, { "locale": "es", "translations": [{ "msgid": "Authentication required", "msgstr": ["Se requiere autenticación"] }, { "msgid": "Checking password …", "msgstr": ["Verificando contraseña …"] }, { "msgid": "Confirm", "msgstr": ["Confirmar"] }, { "msgid": "Password", "msgstr": ["Contraseña"] }, { "msgid": "Please enter your password", "msgstr": ["Por favor, Introduzca su contraseña"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Esta acción requiere autenticación, por favor, confírmela ingresado su contraseña."] }, { "msgid": "Wrong password", "msgstr": ["Contraseña errónea"] }] }, { "locale": "es_AR", "translations": [{ "msgid": "Checking password …", "msgstr": ["Verificando contraseña …"] }, { "msgid": "Confirm", "msgstr": ["Confirmar"] }, { "msgid": "Confirm your password", "msgstr": ["Confirme su contraseña"] }, { "msgid": "Password", "msgstr": ["Contraseña"] }, { "msgid": "Please enter your password", "msgstr": ["Por favor, introduzca su contraseña"] }, { "msgid": "This action needs authentication", "msgstr": ["Esta acción necesita autenticación"] }, { "msgid": "Wrong password", "msgstr": ["Contraseña incorrecta"] }] }, { "locale": "es_CO", "translations": [{ "msgid": "Authentication required", "msgstr": ["Autenticación requerida"] }, { "msgid": "Checking password …", "msgstr": ["Verificando contraseña …"] }, { "msgid": "Confirm", "msgstr": ["Confirmar"] }, { "msgid": "Password", "msgstr": ["Contraseña"] }, { "msgid": "Please enter your password", "msgstr": ["Por favor introduzca su contraseña"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Esta acción necesita autentificación, por favor confírmela introduciendo su contraseña."] }, { "msgid": "Wrong password", "msgstr": ["Contraseña incorrecta"] }] }, { "locale": "es_MX", "translations": [{ "msgid": "Checking password …", "msgstr": ["Verificando contraseña …"] }, { "msgid": "Confirm", "msgstr": ["Confirmar"] }, { "msgid": "Confirm your password", "msgstr": ["Confirme su contraseña"] }, { "msgid": "Password", "msgstr": ["Contraseña"] }, { "msgid": "Please enter your password", "msgstr": ["Por favor introduzca su contraseña"] }, { "msgid": "This action needs authentication", "msgstr": ["Esta acción necesita autenticación"] }, { "msgid": "Wrong password", "msgstr": ["Contraseña incorrecta"] }] }, { "locale": "et_EE", "translations": [{ "msgid": "Authentication required", "msgstr": ["Autentimine on vajalik"] }, { "msgid": "Checking password …", "msgstr": ["Kontrollin salasõna…"] }, { "msgid": "Confirm", "msgstr": ["Kinnita"] }, { "msgid": "Password", "msgstr": ["Salasõna"] }, { "msgid": "Please enter your password", "msgstr": ["Palun sisesta oma salasõna"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["See tegevus eeldab autentimist, palun tee seda sisestades oma salasõna."] }, { "msgid": "Wrong password", "msgstr": ["Vale salasõna"] }] }, { "locale": "fa", "translations": [{ "msgid": "Authentication required", "msgstr": ["احراز هویت مورد نیاز است"] }, { "msgid": "Checking password …", "msgstr": ["در حال بررسی رمز عبور..."] }, { "msgid": "Confirm", "msgstr": ["تأیید"] }, { "msgid": "Password", "msgstr": ["رمز عبور"] }, { "msgid": "Please enter your password", "msgstr": ["لطفاً رمز عبور خود را وارد کنید"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["این عمل نیاز به احراز هویت دارد، لطفاً با وارد کردن رمز عبور خود آن را تأیید کنید."] }, { "msgid": "Wrong password", "msgstr": ["رمز عبور اشتباه است"] }] }, { "locale": "fi_FI", "translations": [{ "msgid": "Authentication required", "msgstr": ["Tunnistautuminen vaaditaan"] }, { "msgid": "Checking password …", "msgstr": ["Tarkistetaan salasanaa …"] }, { "msgid": "Confirm", "msgstr": ["Vahvista"] }, { "msgid": "Password", "msgstr": ["Salasana"] }, { "msgid": "Please enter your password", "msgstr": ["Kirjoita salasanasi"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Tämä toimenpide vaatii tunnistautumisen. Vahvista kirjoittamalla salasanasi."] }, { "msgid": "Wrong password", "msgstr": ["Väärä salasana"] }] }, { "locale": "fr", "translations": [{ "msgid": "Authentication required", "msgstr": ["Authentification requise"] }, { "msgid": "Checking password …", "msgstr": ["Vérification du mot de passe ..."] }, { "msgid": "Confirm", "msgstr": ["Confirmer"] }, { "msgid": "Password", "msgstr": ["Mot de passe"] }, { "msgid": "Please enter your password", "msgstr": ["Veuillez saisir votre mot de passe"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Cette action nécessite une authentification, veuillez confirmer en saisissant votre mot de passe."] }, { "msgid": "Wrong password", "msgstr": ["Mot de passe incorrect"] }] }, { "locale": "ga", "translations": [{ "msgid": "Authentication required", "msgstr": ["Fíordheimhniú ag teastáil"] }, { "msgid": "Checking password …", "msgstr": ["Ag seiceáil an focal faire …"] }, { "msgid": "Confirm", "msgstr": ["Deimhnigh"] }, { "msgid": "Password", "msgstr": ["Pasfhocal"] }, { "msgid": "Please enter your password", "msgstr": ["Cuir isteach do phasfhocal"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Teastaíonn fíordheimhniú don ghníomh seo, deimhnigh é trí do phasfhocal a iontráil."] }, { "msgid": "Wrong password", "msgstr": ["Pasfhocal mícheart"] }] }, { "locale": "gl", "translations": [{ "msgid": "Authentication required", "msgstr": ["É necesaria a autenticación"] }, { "msgid": "Checking password …", "msgstr": ["Comprobando o contrasinal…"] }, { "msgid": "Confirm", "msgstr": ["Confirmar"] }, { "msgid": "Password", "msgstr": ["Contrasinal"] }, { "msgid": "Please enter your password", "msgstr": ["Introduza o seu contrasinal"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Esta acción precisa de autenticación, confírmea introducindo o seu contrasinal."] }, { "msgid": "Wrong password", "msgstr": ["Contrasinal incorrecto"] }] }, { "locale": "hr", "translations": [{ "msgid": "Authentication required", "msgstr": ["Potrebna je autentifikacija"] }, { "msgid": "Checking password …", "msgstr": ["Provjera lozinke …"] }, { "msgid": "Confirm", "msgstr": ["Potvrdi"] }, { "msgid": "Password", "msgstr": ["Lozinka"] }, { "msgid": "Please enter your password", "msgstr": ["Molimo unesite vašu lozinku"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Ova radnja zahtijeva autentifikaciju, potvrdite je unosom svoje lozinke."] }, { "msgid": "Wrong password", "msgstr": ["Pogrešna lozinka"] }] }, { "locale": "hu_HU", "translations": [{ "msgid": "Authentication required", "msgstr": ["Azonosítás szükséges"] }, { "msgid": "Checking password …", "msgstr": ["Jelszó ellenőrzése ..."] }, { "msgid": "Confirm", "msgstr": ["Jóváhagyás"] }, { "msgid": "Password", "msgstr": ["Jelszó"] }, { "msgid": "Please enter your password", "msgstr": ["Adja meg a jelszavát"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Ehhez a tevékenységhez azonosítás szükséges. Kérlek hagyd jóvá a jelszavad megadásával."] }, { "msgid": "Wrong password", "msgstr": ["Hibás jelszó"] }] }, { "locale": "id", "translations": [{ "msgid": "Authentication required", "msgstr": ["Autentikasi diperlukan"] }, { "msgid": "Checking password …", "msgstr": ["Memeriksa kata sandi ..."] }, { "msgid": "Confirm", "msgstr": ["Konfirmasi"] }, { "msgid": "Password", "msgstr": ["Kata sandi"] }, { "msgid": "Please enter your password", "msgstr": ["Silahkan masukan kata sandi Anda"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Aksi ini memerlukan autentikasi, silahkan konfirmasi dengan memasukan kata sandi Anda."] }, { "msgid": "Wrong password", "msgstr": ["Kata sandi salah"] }] }, { "locale": "is", "translations": [{ "msgid": "Checking password …", "msgstr": ["Athuga lykilorð …"] }, { "msgid": "Confirm", "msgstr": ["Staðfesta"] }, { "msgid": "Confirm your password", "msgstr": ["Staðfestu lykilorðið þitt"] }, { "msgid": "Password", "msgstr": ["Lykilorð"] }, { "msgid": "Please enter your password", "msgstr": ["Settu inn lykilorðið þitt"] }, { "msgid": "This action needs authentication", "msgstr": ["Þessi aðgerð krefst auðkenningar"] }, { "msgid": "Wrong password", "msgstr": ["Rangt lykilorð"] }] }, { "locale": "it", "translations": [{ "msgid": "Authentication required", "msgstr": ["Autenticazione richiesta"] }, { "msgid": "Checking password …", "msgstr": ["Controllo della password…"] }, { "msgid": "Confirm", "msgstr": ["Conferma"] }, { "msgid": "Password", "msgstr": ["Password"] }, { "msgid": "Please enter your password", "msgstr": ["Digita la tua password"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Questa azione richiede l'autenticazione, confermala digitando la tua password."] }, { "msgid": "Wrong password", "msgstr": ["Password errata"] }] }, { "locale": "ja_JP", "translations": [{ "msgid": "Authentication required", "msgstr": ["認証が必要です"] }, { "msgid": "Checking password …", "msgstr": ["パスワードの確認 …"] }, { "msgid": "Confirm", "msgstr": ["確認"] }, { "msgid": "Password", "msgstr": ["パスワード"] }, { "msgid": "Please enter your password", "msgstr": ["パスワードを入力してください"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["この操作には認証が必要です。パスワードを入力してください。"] }, { "msgid": "Wrong password", "msgstr": ["間違ったパスワード"] }] }, { "locale": "kab", "translations": [{ "msgid": "Authentication required", "msgstr": ["Asesteb yettwasra"] }, { "msgid": "Checking password …", "msgstr": ["Asenqed n wawal n uɛeddi …"] }, { "msgid": "Confirm", "msgstr": ["Sentem"] }, { "msgid": "Password", "msgstr": ["Awal n uɛeddi"] }, { "msgid": "Please enter your password", "msgstr": ["Txil, sekcem-d awal-ik·im n uɛeddi"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Tigawt-a tesra asesteb, ttxil-k·m, wekked-itt-id s usekcem n wawal-inek·inem n uɛeddi."] }, { "msgid": "Wrong password", "msgstr": ["Awal n uɛeddi d arameɣtu"] }] }, { "locale": "ko", "translations": [{ "msgid": "Authentication required", "msgstr": ["인증 필요 "] }, { "msgid": "Checking password …", "msgstr": ["비밀번호 확인 중 ..."] }, { "msgid": "Confirm", "msgstr": ["확인"] }, { "msgid": "Password", "msgstr": ["비밀번호"] }, { "msgid": "Please enter your password", "msgstr": ["비밀번호를 입력하세요"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["이 작업은 인증이 필요합니다. 비밀번호를 입력하여 확인하십시오. "] }, { "msgid": "Wrong password", "msgstr": ["잘못된 비밀번호"] }] }, { "locale": "lv", "translations": [{ "msgid": "Authentication required", "msgstr": ["Nepieciešama autentificēšanās"] }, { "msgid": "Checking password …", "msgstr": ["Pārbauda paroli..."] }, { "msgid": "Confirm", "msgstr": ["Apstiprināt"] }, { "msgid": "Password", "msgstr": ["Parole"] }, { "msgid": "Please enter your password", "msgstr": ["Lūgums ievadīt savu paroli"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Šai darbībai ir nepieciešama autentificēšanās. Lūgums to apstiprināt ar savas paroles ievadīšanu."] }, { "msgid": "Wrong password", "msgstr": ["Nepareiza parole"] }] }, { "locale": "mk", "translations": [{ "msgid": "Authentication required", "msgstr": ["Потребна е автентификација"] }, { "msgid": "Checking password …", "msgstr": ["Проверка на лозинка …"] }, { "msgid": "Confirm", "msgstr": ["Потврди"] }, { "msgid": "Password", "msgstr": ["Лозинка"] }, { "msgid": "Please enter your password", "msgstr": ["Внесете ја вашата лозинка"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Оваа акција бара автентикација, потврдете ја со внесување на вашата лозинка."] }, { "msgid": "Wrong password", "msgstr": ["Погрешна лозинка"] }] }, { "locale": "ms_MY", "translations": [{ "msgid": "Authentication required", "msgstr": ["Pengesahan diperlukan"] }, { "msgid": "Checking password …", "msgstr": ["Menyemak kata laluan …"] }, { "msgid": "Confirm", "msgstr": ["Mengesahkan"] }, { "msgid": "Password", "msgstr": ["Kata laluan"] }, { "msgid": "Please enter your password", "msgstr": ["Sila masukkan kata laluan anda"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Tindakan ini memerlukan pengesahan, sila sahkan dengan memasukkan kata laluan anda."] }, { "msgid": "Wrong password", "msgstr": ["Kata laluan salah"] }] }, { "locale": "nb_NO", "translations": [{ "msgid": "Checking password …", "msgstr": ["Sjekker passord …"] }, { "msgid": "Confirm", "msgstr": ["Bekreft"] }, { "msgid": "Confirm your password", "msgstr": ["Bekreft passordet ditt"] }, { "msgid": "Password", "msgstr": ["Passord"] }, { "msgid": "Please enter your password", "msgstr": ["Vennligst skriv inn passordet ditt"] }, { "msgid": "This action needs authentication", "msgstr": ["Denne handlingen krever autentisering"] }, { "msgid": "Wrong password", "msgstr": ["Feil passord"] }] }, { "locale": "nl", "translations": [{ "msgid": "Authentication required", "msgstr": ["Authenticatie vereist"] }, { "msgid": "Checking password …", "msgstr": ["Wachtwoord controleren…"] }, { "msgid": "Confirm", "msgstr": ["Bevestigen"] }, { "msgid": "Password", "msgstr": ["Wachtwoord"] }, { "msgid": "Please enter your password", "msgstr": ["Voer je wachtwoord in"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Deze actie vereist authenticatie, voer je wachtwoord in."] }, { "msgid": "Wrong password", "msgstr": ["Incorrect wachtwoord"] }] }, { "locale": "pl", "translations": [{ "msgid": "Checking password …", "msgstr": ["Sprawdzanie hasła…"] }, { "msgid": "Confirm", "msgstr": ["Potwierdź"] }, { "msgid": "Confirm your password", "msgstr": ["Potwierdź swoje hasło"] }, { "msgid": "Password", "msgstr": ["Hasło"] }, { "msgid": "Please enter your password", "msgstr": ["Wprowadź swoje hasło"] }, { "msgid": "This action needs authentication", "msgstr": ["Wykonanie tej czynności wymaga autoryzacji"] }, { "msgid": "Wrong password", "msgstr": ["Nieprawidłowe hasło"] }] }, { "locale": "pt_BR", "translations": [{ "msgid": "Authentication required", "msgstr": ["Autenticação necessária"] }, { "msgid": "Checking password …", "msgstr": ["Verificando a senha …"] }, { "msgid": "Confirm", "msgstr": ["Confirmar"] }, { "msgid": "Password", "msgstr": ["Senha"] }, { "msgid": "Please enter your password", "msgstr": ["Por favor, insira sua senha"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Esta ação precisa de autenticação. Por favor, confirme-a digitando sua senha."] }, { "msgid": "Wrong password", "msgstr": ["Senha incorreta"] }] }, { "locale": "pt_PT", "translations": [{ "msgid": "Authentication required", "msgstr": ["Autenticação necessária"] }, { "msgid": "Checking password …", "msgstr": ["A verificar palavra-passe…"] }, { "msgid": "Confirm", "msgstr": ["Confirmar"] }, { "msgid": "Password", "msgstr": ["Palavra-passe"] }, { "msgid": "Please enter your password", "msgstr": ["Introduza a sua palavra-passe, por favor"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Esta ação requer autenticação; confirme-a introduzindo a sua palavra-passe."] }, { "msgid": "Wrong password", "msgstr": ["Palavra-passe incorreta"] }] }, { "locale": "ro", "translations": [{ "msgid": "Checking password …", "msgstr": ["Se verifică parola ..."] }, { "msgid": "Confirm", "msgstr": ["Confirmare"] }, { "msgid": "Confirm your password", "msgstr": ["Confirmare parolă"] }, { "msgid": "Password", "msgstr": ["Parolă"] }, { "msgid": "Please enter your password", "msgstr": ["Vă rugăm să introduceți parola"] }, { "msgid": "This action needs authentication", "msgstr": ["Această acțiune necesită autentificare"] }, { "msgid": "Wrong password", "msgstr": ["Parolă incorectă"] }] }, { "locale": "ru", "translations": [{ "msgid": "Authentication required", "msgstr": ["Требуется аутентификация"] }, { "msgid": "Checking password …", "msgstr": ["Проверка пароля …"] }, { "msgid": "Confirm", "msgstr": ["Подтвердить"] }, { "msgid": "Password", "msgstr": ["Пароль"] }, { "msgid": "Please enter your password", "msgstr": ["Пожалуйста, введите свой пароль"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Это действие требует аутентификации, пожалуйста подтвердите его вводом вашего пароля."] }, { "msgid": "Wrong password", "msgstr": ["Неправильный пароль"] }] }, { "locale": "sk_SK", "translations": [{ "msgid": "Authentication required", "msgstr": ["Vyžaduje sa autentifikácia"] }, { "msgid": "Checking password …", "msgstr": ["Kontrola hesla…"] }, { "msgid": "Confirm", "msgstr": ["Potvrdiť"] }, { "msgid": "Password", "msgstr": ["Heslo"] }, { "msgid": "Please enter your password", "msgstr": ["Zadajte prosím svoje heslo"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Táto akcia vyžaduje overenie, prosím potvrďte ju zadaním vášho hesla."] }, { "msgid": "Wrong password", "msgstr": ["Nesprávne heslo"] }] }, { "locale": "sl", "translations": [{ "msgid": "Authentication required", "msgstr": ["Zahtevana avtentikacija"] }, { "msgid": "Checking password …", "msgstr": ["Poteka preverjanje gesla ..."] }, { "msgid": "Confirm", "msgstr": ["Potrdi"] }, { "msgid": "Password", "msgstr": ["Geslo"] }, { "msgid": "Please enter your password", "msgstr": ["Vpisati je treba geslo"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["To dejanje zahteva preverjanje pristnosti, potrdite ga z vnosom gesla."] }, { "msgid": "Wrong password", "msgstr": ["Napačno geslo"] }] }, { "locale": "sr", "translations": [{ "msgid": "Authentication required", "msgstr": ["Потребна је потврда идентитета"] }, { "msgid": "Checking password …", "msgstr": ["Проверава се лозинка…"] }, { "msgid": "Confirm", "msgstr": ["Потврда"] }, { "msgid": "Password", "msgstr": ["Лозинка"] }, { "msgid": "Please enter your password", "msgstr": ["Молимо вас да унесете своју лозинку"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["За ову акцију је потребна провера идентитета, молимо вас да га потврдите уносом своје лозинке."] }, { "msgid": "Wrong password", "msgstr": ["Погрешна лозинка"] }] }, { "locale": "sv", "translations": [{ "msgid": "Authentication required", "msgstr": ["Autentisering krävs"] }, { "msgid": "Checking password …", "msgstr": ["Kontrollerar lösenord …"] }, { "msgid": "Confirm", "msgstr": ["Bekräfta"] }, { "msgid": "Password", "msgstr": ["Lösenord"] }, { "msgid": "Please enter your password", "msgstr": ["Ange ditt lösenord"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Den här åtgärden kräver autentisering, bekräfta genom att ange ditt lösenord."] }, { "msgid": "Wrong password", "msgstr": ["Fel lösenord"] }] }, { "locale": "tr", "translations": [{ "msgid": "Authentication required", "msgstr": ["Kimlik doğrulaması gerekli"] }, { "msgid": "Checking password …", "msgstr": ["Parola denetleniyor…"] }, { "msgid": "Confirm", "msgstr": ["Parola onayı"] }, { "msgid": "Password", "msgstr": ["Parola"] }, { "msgid": "Please enter your password", "msgstr": ["Lütfen parolanızı yazın"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Bu işlemi yapmak için kimliğinizi doğrulamalısınız. Lütfen parolanızı yazın."] }, { "msgid": "Wrong password", "msgstr": ["Parola geçersiz"] }] }, { "locale": "uk", "translations": [{ "msgid": "Authentication required", "msgstr": ["Потрібна авторизація"] }, { "msgid": "Checking password …", "msgstr": ["Перевірка паролю ..."] }, { "msgid": "Confirm", "msgstr": ["Підтвердити"] }, { "msgid": "Password", "msgstr": ["Пароль"] }, { "msgid": "Please enter your password", "msgstr": ["Зазначте ваш пароль"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Ця дія вимагає авторизацію, зазначте ваш пароль."] }, { "msgid": "Wrong password", "msgstr": ["Помилковий пароль"] }] }, { "locale": "ur_PK", "translations": [{ "msgid": "Authentication required", "msgstr": ["تصدیق درکار ہے"] }, { "msgid": "Checking password …", "msgstr": ["پاس ورڈ چیک ہو رہا ہے …"] }, { "msgid": "Confirm", "msgstr": ["تصدیق کریں"] }, { "msgid": "Password", "msgstr": ["پاس ورڈ"] }, { "msgid": "Please enter your password", "msgstr": ["براہ کرم اپنا پاس ورڈ درج کریں"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["اس عمل کو تصدیق کی ضرورت ہے، براہ کرم پاس ورڈ درج کرکے اس کی تصدیق کریں۔"] }, { "msgid": "Wrong password", "msgstr": ["نادرست پاس ورڈ"] }] }, { "locale": "uz", "translations": [{ "msgid": "Authentication required", "msgstr": ["Autentifikatsiya talab qilinadi"] }, { "msgid": "Checking password …", "msgstr": ["Parol tekshirilmoqda…"] }, { "msgid": "Confirm", "msgstr": ["Tasdiqlang"] }, { "msgid": "Password", "msgstr": ["Parol"] }, { "msgid": "Please enter your password", "msgstr": ["Iltimos, parolingizni kiriting"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Ushbu amaliyot autentifikatsiya talab etadi, parolingizni kiritish orqali buni tasdiqlang."] }, { "msgid": "Wrong password", "msgstr": ["Parol noto'g'ri "] }] }, { "locale": "vi", "translations": [{ "msgid": "Authentication required", "msgstr": ["Yêu cầu xác thực"] }, { "msgid": "Checking password …", "msgstr": ["Đang kiểm tra mật khẩu ..."] }, { "msgid": "Confirm", "msgstr": ["Chấp nhận"] }, { "msgid": "Password", "msgstr": ["Mật khẩu"] }, { "msgid": "Please enter your password", "msgstr": ["Vui lòng nhập mật khẩu của bạn"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["Thao tác này cần xác thực, vui lòng xác nhận bằng cách nhập mật khẩu của bạn."] }, { "msgid": "Wrong password", "msgstr": ["Mật khẩu sai"] }] }, { "locale": "zh_CN", "translations": [{ "msgid": "Authentication required", "msgstr": ["需要身份验证"] }, { "msgid": "Checking password …", "msgstr": ["正在检查密码 …"] }, { "msgid": "Confirm", "msgstr": ["确认"] }, { "msgid": "Password", "msgstr": ["密码"] }, { "msgid": "Please enter your password", "msgstr": ["请输入您的密码"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["此操作需要身份验证，请输入密码进行确认。"] }, { "msgid": "Wrong password", "msgstr": ["密码错误"] }] }, { "locale": "zh_HK", "translations": [{ "msgid": "Authentication required", "msgstr": ["需要驗證"] }, { "msgid": "Checking password …", "msgstr": ["正在檢查密碼 …"] }, { "msgid": "Confirm", "msgstr": ["確認"] }, { "msgid": "Password", "msgstr": ["密碼"] }, { "msgid": "Please enter your password", "msgstr": ["請輸入您的密碼"] }, { "msgid": "This action needs authentication, please confirm it by entering your password.", "msgstr": ["此操作需要身份驗證，請輸入您的密碼以進行確認。"] }, { "msgid": "Wrong password", "msgstr": ["密碼錯誤"] }] }, { "locale": "zh_TW", "translations": [{ "msgid": "Checking password …", "msgstr": ["正在檢查密碼……"] }, { "msgid": "Confirm", "msgstr": ["確認"] }, { "msgid": "Confirm your password", "msgstr": ["確認您的密碼"] }, { "msgid": "Password", "msgstr": ["密碼"] }, { "msgid": "Please enter your password", "msgstr": ["請輸入您的密碼"] }, { "msgid": "This action needs authentication", "msgstr": ["此動作需要驗證"] }, { "msgid": "Wrong password", "msgstr": ["密碼錯誤"] }] }].map(({ locale, translations }) => gtBuilder.addTranslation(locale, {
  headers: {},
  translations: {
    "": Object.fromEntries(translations.map((t2) => [t2.msgid, t2]))
  }
}));
const gt = gtBuilder.build();
gt.ngettext.bind(gt);
const t = gt.gettext.bind(gt);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "PasswordDialog",
  props: {
    validate: { type: Function }
  },
  emits: ["close"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    onMounted(focusPasswordField);
    const passwordInput = useTemplateRef("field");
    const password = ref("");
    const loading = ref(false);
    const hasError = ref(false);
    const buttons = [{
      label: t("Confirm"),
      type: "submit",
      variant: "primary",
      callback
    }];
    const helperText = computed(() => {
      if (hasError.value) {
        return t("Wrong password");
      }
      if (loading.value) {
        return t("Checking password …");
      }
      if (password.value === "") {
        return t("Please enter your password");
      }
      return "";
    });
    async function callback() {
      hasError.value = false;
      loading.value = true;
      if (password.value === "") {
        hasError.value = true;
        return false;
      }
      try {
        await props.validate(password.value);
        emit("close", true);
      } catch (error) {
        if (isConfirmationError(error)) {
          hasError.value = true;
          logger.error("Exception during password confirmation", { error });
          selectPasswordField();
          return false;
        }
        hasError.value = true;
        emit("close", false);
      } finally {
        loading.value = false;
      }
      return true;
    }
    function focusPasswordField() {
      nextTick(() => {
        passwordInput.value.focus();
      });
    }
    function selectPasswordField() {
      nextTick(() => {
        passwordInput.value.select();
      });
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcDialog), {
        isForm: "",
        buttons,
        name: unref(t)("Authentication required"),
        contentClasses: _ctx.$style.passwordDialog,
        "onUpdate:open": _cache[1] || (_cache[1] = ($event) => emit("close", false))
      }, {
        default: withCtx(() => [
          createBaseVNode("p", null, toDisplayString(unref(t)("This action needs authentication, please confirm it by entering your password.")), 1),
          createVNode(unref(NcPasswordField), {
            ref: "field",
            modelValue: password.value,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => password.value = $event),
            label: unref(t)("Password"),
            helperText: helperText.value,
            checkPasswordStrength: false,
            error: hasError.value !== false,
            required: ""
          }, null, 8, ["modelValue", "label", "helperText", "error"])
        ]),
        _: 1
      }, 8, ["name", "contentClasses"]);
    };
  }
});
const passwordDialog = "_passwordDialog_joix2_2";
const style0 = {
  passwordDialog
};
const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
const cssModules = {
  "$style": style0
};
const PasswordDialogVue = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__cssModules", cssModules]]);
async function confirmPassword() {
  if (!isPasswordConfirmationRequired(PwdConfirmationMode.Lax)) {
    return Promise.resolve();
  }
  await promptPassword(async (password) => {
    await _confirmPassword(password);
  });
}
async function _confirmPassword(password) {
  logger.debug("Confirming password");
  const url = generateUrl("/login/confirm");
  const { data } = await cancelableClient.post(url, { password });
  window.nc_lastLogin = data.lastLogin;
  logger.debug("Password confirmed");
}
let _passwordDialog;
let _dialogCallback;
async function promptPassword(validate) {
  _dialogCallback = validate;
  if (!_passwordDialog) {
    _passwordDialog = spawnDialog(PasswordDialogVue, {
      validate(password) {
        return _dialogCallback(password);
      }
    });
  }
  const result = await _passwordDialog;
  _passwordDialog = void 0;
  if (!result) {
    throw new Error("Dialog closed");
  }
}
const _hoisted_1 = { class: "federated-teams__sub-section" };
const _hoisted_2 = { class: "federated-teams__hint" };
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "AdminSettings",
  setup(__props) {
    const federatedTeamsEnabled = ref(Boolean(loadState("circles", "federatedTeamsEnabled", false)));
    const federatedTeamsFrontal = ref(loadState("circles", "federatedTeamsFrontal", ""));
    function parseFederatedTeamsFrontal(url) {
      try {
        const parsed = new URL(url);
        const scheme = parsed.protocol.replace(":", "");
        let cloudId = parsed.hostname;
        const port = parsed.port;
        let path = parsed.pathname;
        if (!scheme || !cloudId) {
          return { scheme: null, cloudId: null, path: "" };
        }
        if (!path || path === "/") {
          path = "";
        } else {
          path = path.replace(/^\//, "").replace(/\/$/, "");
        }
        if (port) {
          cloudId += ":" + port;
        }
        return { scheme, cloudId, path };
      } catch {
        return { scheme: null, cloudId: null, path: "" };
      }
    }
    async function updateAppConfig(key, value) {
      await confirmPassword();
      const url = generateOcsUrl("/apps/circles/settings/{key}", {
        appId: "circles",
        key
      });
      try {
        const { data } = await cancelableClient.post(url, {
          value
        });
        if (data.ocs.meta.status !== "ok") {
          if (data.ocs.meta.message) {
            showError(translate("circles", "Unable to update federated teams config"));
            logger$1.error("Error while updating federated teams config", { error: data.ocs });
          } else {
            throw new Error(`${data.ocs.meta.statuscode}`);
          }
        }
      } catch (error) {
        showError(translate("circles", "Unable to update federated teams config"));
        logger$1.error("Error while updating federated teams config", { error });
      }
    }
    function onToggleFederatedTeams() {
      const value = federatedTeamsEnabled.value ? "yes" : "no";
      updateAppConfig("federated_teams_enabled", value);
    }
    watchDebounced(federatedTeamsFrontal, async (value) => {
      const { scheme, cloudId } = parseFederatedTeamsFrontal(value);
      if (scheme === null || cloudId === null) {
        showError(translate("circles", "Invalid URL format. Please provide a valid URL."));
        return;
      }
      await updateAppConfig("federated_teams_frontal", value);
      showSuccess(translate("circles", "Changed frontal cloud URL"));
    }, { debounce: 500 });
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcSettingsSection), {
        name: unref(translate)("circles", "Federated Teams"),
        description: unref(translate)("circles", "Federation allows you to share teams with other trusted servers and make them discoverable across instances.")
      }, {
        default: withCtx(() => [
          createVNode(unref(NcCheckboxRadioSwitch), {
            modelValue: federatedTeamsEnabled.value,
            "onUpdate:modelValue": [
              _cache[0] || (_cache[0] = ($event) => federatedTeamsEnabled.value = $event),
              onToggleFederatedTeams
            ],
            type: "switch"
          }, {
            default: withCtx(() => [
              createTextVNode(toDisplayString(unref(translate)("circles", "Enable federated teams")), 1)
            ]),
            _: 1
          }, 8, ["modelValue"]),
          withDirectives(createBaseVNode("div", _hoisted_1, [
            createVNode(unref(_sfc_main$2), {
              modelValue: federatedTeamsFrontal.value,
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => federatedTeamsFrontal.value = $event),
              label: unref(translate)("circles", "Frontal URL"),
              placeholder: unref(translate)("circles", "https://…"),
              type: "url",
              class: "federated-teams__input"
            }, null, 8, ["modelValue", "label", "placeholder"]),
            createBaseVNode("p", _hoisted_2, toDisplayString(unref(translate)("circles", "The public URL used by other instances to discover and connect to your teams.")), 1)
          ], 512), [
            [vShow, federatedTeamsEnabled.value]
          ])
        ]),
        _: 1
      }, 8, ["name", "description"]);
    };
  }
});
const AdminSettings = /* @__PURE__ */ _export_sfc$1(_sfc_main, [["__scopeId", "data-v-2c8100aa"]]);
(function polyfill() {
  const relList = document.createElement("link").relList;
  if (relList && relList.supports && relList.supports("modulepreload")) return;
  for (const link of document.querySelectorAll('link[rel="modulepreload"]')) processPreload(link);
  new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      if (mutation.type !== "childList") continue;
      for (const node of mutation.addedNodes) if (node.tagName === "LINK" && node.rel === "modulepreload") processPreload(node);
    }
  }).observe(document, {
    childList: true,
    subtree: true
  });
  function getFetchOpts(link) {
    const fetchOpts = {};
    if (link.integrity) fetchOpts.integrity = link.integrity;
    if (link.referrerPolicy) fetchOpts.referrerPolicy = link.referrerPolicy;
    if (link.crossOrigin === "use-credentials") fetchOpts.credentials = "include";
    else if (link.crossOrigin === "anonymous") fetchOpts.credentials = "omit";
    else fetchOpts.credentials = "same-origin";
    return fetchOpts;
  }
  function processPreload(link) {
    if (link.ep) return;
    link.ep = true;
    const fetchOpts = getFetchOpts(link);
    fetch(link.href, fetchOpts);
  }
})();
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const app = createApp(AdminSettings);
app.mount("#vue-admin-federated-teams");
//# sourceMappingURL=teams-settings-admin.mjs.map
