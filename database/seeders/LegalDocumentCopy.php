<?php

namespace Database\Seeders;

use App\Models\ContentBlock;

class LegalDocumentCopy
{
    /**
     * @return list<array{slug: string, title: array<string, string>, body: array<string, string>}>
     */
    public function blocks(): array
    {
        return [
            [
                'slug' => ContentBlock::LEGAL_PRIVACY,
                'title' => [
                    'en' => 'Privacy Policy',
                    'es' => 'Política de privacidad',
                    'ru' => 'Политика конфиденциальности',
                    'fr' => 'Politique de confidentialité',
                    'de' => 'Datenschutzrichtlinie',
                    'tg' => 'Сиёсати махфият',
                ],
                'body' => [
                    'en' => $this->privacyEn(),
                    'es' => $this->privacyEs(),
                    'ru' => $this->privacyRu(),
                    'fr' => $this->privacyFr(),
                    'de' => $this->privacyDe(),
                    'tg' => $this->privacyTg(),
                ],
            ],
            [
                'slug' => ContentBlock::LEGAL_TERMS,
                'title' => [
                    'en' => 'Terms & Conditions',
                    'es' => 'Términos y condiciones',
                    'ru' => 'Условия использования',
                    'fr' => 'Conditions générales',
                    'de' => 'Allgemeine Geschäftsbedingungen',
                    'tg' => 'Шартҳо ва қоидаҳо',
                ],
                'body' => [
                    'en' => $this->termsEn(),
                    'es' => $this->termsEs(),
                    'ru' => $this->termsRu(),
                    'fr' => $this->termsFr(),
                    'de' => $this->termsDe(),
                    'tg' => $this->termsTg(),
                ],
            ],
            [
                'slug' => ContentBlock::LEGAL_DELETE_ACCOUNT,
                'title' => [
                    'en' => 'Delete account',
                    'es' => 'Eliminar cuenta',
                    'ru' => 'Удаление аккаунта',
                    'fr' => 'Supprimer le compte',
                    'de' => 'Konto löschen',
                    'tg' => 'Нест кардани ҳисоб',
                ],
                'body' => [
                    'en' => $this->deleteEn(),
                    'es' => $this->deleteEs(),
                    'ru' => $this->deleteRu(),
                    'fr' => $this->deleteFr(),
                    'de' => $this->deleteDe(),
                    'tg' => $this->deleteTg(),
                ],
            ],
        ];
    }

    protected function privacyEn(): string
    {
        return <<<'TXT'
This Privacy Policy explains how ZoodeSIM (“we”, “us”) collects, uses, shares, and protects personal information when you use our mobile app, websites, partner portal, and eSIM services.

## 1. Who we are
ZoodeSIM provides consumer eSIM connectivity, wallet features, referral rewards, and a partner programme. This policy applies to customer accounts, partner applications, and visitors of our public pages.

## 2. Information we collect
We collect information you provide and information generated when you use the service:
- Account details such as name, email address, and phone number
- Sign-in identifiers from Google, Apple, Facebook, or email/password (including a Firebase user ID)
- Device identifiers, IP address, and approximate network location used for fraud prevention
- Purchase history, eSIM orders, wallet balances, and referral or promo activity
- Support messages and records needed to investigate disputes

## 3. How we use information
We use personal information to:
- Create and secure your account, and keep you signed in
- Fulfil eSIM orders, process payments, and maintain your wallet
- Apply referral codes, partner commissions, and promotional bonuses
- Detect abuse, duplicate accounts, and unauthorised access
- Send transactional notices (order status, security, and account changes)
- Improve reliability, localisation, and customer support

We do not sell your personal information.

## 4. Sharing
We share information only as needed to operate ZoodeSIM:
- Connectivity and provisioning partners who deliver eSIM profiles
- Payment, authentication, and cloud infrastructure providers
- Partners you chose to join through a promo or referral relationship, limited to programme reporting
- Professional advisers or authorities when required by law or to protect the service

## 5. Retention
We keep account, order, and wallet records for as long as your account is active and for a reasonable period afterwards to meet accounting, fraud, and legal duties. If you delete your account, it is deactivated immediately; administrators may retain historical records as described on the Delete account page.

## 6. Security
We use access controls, encrypted transport, and token-based sessions. No method of transmission is completely secure. Please protect your device and sign-in methods.

## 7. Your choices
Depending on where you live, you may request access, correction, or deletion of personal information, or object to certain processing. You can update profile details in the app and delete your account from Profile → Delete account. You may also contact support using the details published in the app.

## 8. International processing
Our service and providers may process information in countries other than your own. We take steps appropriate to the nature of the data and the destination.

## 9. Children
ZoodeSIM is intended for adults. We do not knowingly create accounts for children.

## 10. Changes
We may update this policy when our products or legal requirements change. The revised version is published on this page and, where appropriate, in the app. Continued use after an update means you accept the revised policy.

This document is provided for transparency and is not a substitute for legal advice. An administrator can refine the published text at any time.
TXT;
    }

    protected function privacyEs(): string
    {
        return <<<'TXT'
Esta Política de privacidad explica cómo ZoodeSIM (“nosotros”) recopila, usa, comparte y protege la información personal cuando usas la app, los sitios web, el portal de socios y los servicios eSIM.

## 1. Quiénes somos
ZoodeSIM ofrece conectividad eSIM, monedero, recompensas por referidos y un programa de socios. Esta política aplica a cuentas de clientes, solicitudes de socios y visitantes de las páginas públicas.

## 2. Información que recopilamos
Recopilamos datos que nos facilitas y datos generados al usar el servicio:
- Datos de cuenta: nombre, correo y teléfono
- Identificadores de acceso de Google, Apple, Facebook o correo/contraseña (incluido un ID de Firebase)
- Identificadores del dispositivo, IP y ubicación aproximada de red para prevenir fraude
- Historial de compras, pedidos eSIM, saldo del monedero y actividad de referidos o promociones
- Mensajes de soporte y registros para investigar incidencias

## 3. Cómo usamos la información
Usamos la información para:
- Crear y proteger tu cuenta
- Completar pedidos eSIM, pagos y el monedero
- Aplicar códigos de referido, comisiones y bonos
- Detectar abuso, cuentas duplicadas y accesos no autorizados
- Enviar avisos transaccionales
- Mejorar fiabilidad, idiomas y soporte

No vendemos tu información personal.

## 4. Conservación y seguridad
Conservamos cuenta, pedidos y monedero mientras la cuenta esté activa y el tiempo necesario por motivos contables, de fraude y legales. Si eliminas la cuenta, se desactiva de inmediato; los administradores pueden conservar historial. Protegemos el servicio con controles de acceso, transporte cifrado y sesiones con tokens.

## 5. Tus opciones
Puedes solicitar acceso, corrección o eliminación, actualizar el perfil en la app o borrar la cuenta en Perfil → Eliminar cuenta. El servicio puede procesar datos en otros países. ZoodeSIM está pensado para adultos.

## 6. Cambios
Podemos actualizar esta política. La versión vigente se publica aquí y, cuando corresponda, en la app.
TXT;
    }

    protected function privacyRu(): string
    {
        return <<<'TXT'
Настоящая Политика конфиденциальности объясняет, как ZoodeSIM («мы») собирает, использует, передаёт и защищает персональные данные при работе с приложением, сайтами, партнёрским кабинетом и услугами eSIM.

## 1. Кто мы
ZoodeSIM предоставляет eSIM-связь, кошелёк, реферальные награды и партнёрскую программу. Политика распространяется на клиентские аккаунты, заявки партнёров и посетителей публичных страниц.

## 2. Какие данные мы собираем
Мы получаем данные, которые вы указываете, и данные, возникающие при использовании сервиса:
- Имя, email и телефон
- Идентификаторы входа Google, Apple, Facebook или email/пароль (включая Firebase ID)
- Идентификаторы устройства, IP и приблизительное сетевое расположение для защиты от мошенничества
- История покупок, заказы eSIM, баланс кошелька и реферальная активность
- Обращения в поддержку и материалы расследований

## 3. Как мы используем данные
Данные нужны, чтобы создавать аккаунт, выполнять заказы, вести кошелёк, начислять рефералы, предотвращать злоупотребления и отправлять служебные уведомления. Мы не продаём персональные данные.

## 4. Хранение и защита
Данные аккаунта, заказов и кошелька хранятся, пока аккаунт активен, и разумный срок после этого для учёта, антифрода и закона. После удаления аккаунт сразу деактивируется; администраторы могут сохранять историю. Мы применяем контроль доступа, шифрование канала и сессии на токенах.

## 5. Ваши возможности
Вы можете запросить доступ, исправление или удаление, обновить профиль в приложении или удалить аккаунт в Профиль → Удалить аккаунт. Обработка может идти в других странах. Сервис предназначен для взрослых.

## 6. Изменения
Мы можем обновлять политику. Актуальная версия публикуется на этой странице и при необходимости в приложении.
TXT;
    }

    protected function privacyFr(): string
    {
        return <<<'TXT'
Cette politique décrit comment ZoodeSIM (« nous ») collecte, utilise, partage et protège les informations personnelles lorsque vous utilisez l’application, les sites, le portail partenaire et les services eSIM.

## 1. Qui nous sommes
ZoodeSIM fournit la connectivité eSIM, un portefeuille, des récompenses de parrainage et un programme partenaires. Cette politique s’applique aux comptes clients, candidatures partenaires et visiteurs des pages publiques.

## 2. Informations collectées
Nous collectons les données que vous fournissez et celles générées par l’usage du service :
- Nom, e-mail et téléphone
- Identifiants de connexion Google, Apple, Facebook ou e-mail/mot de passe (y compris un ID Firebase)
- Identifiants d’appareil, adresse IP et localisation réseau approximative anti-fraude
- Historique d’achats, commandes eSIM, solde du portefeuille et activité de parrainage
- Messages d’assistance et dossiers d’enquête

## 3. Utilisation
Nous utilisons ces informations pour créer le compte, livrer les eSIM, gérer le portefeuille, appliquer les parrainages, prévenir les abus et envoyer des avis transactionnels. Nous ne vendons pas vos données.

## 4. Conservation et sécurité
Les comptes, commandes et portefeuilles sont conservés pendant la vie du compte et un délai raisonnable ensuite. Après suppression, le compte est désactivé immédiatement ; l’historique peut rester visible aux administrateurs. Nous utilisons des contrôles d’accès, un transport chiffré et des sessions par jeton.

## 5. Vos choix
Vous pouvez demander l’accès, la correction ou la suppression, mettre à jour le profil dans l’app ou supprimer le compte via Profil → Supprimer le compte. Le traitement peut avoir lieu hors de votre pays. ZoodeSIM s’adresse aux adultes.

## 6. Modifications
Nous pouvons mettre à jour cette politique. La version en vigueur est publiée ici et, le cas échéant, dans l’application.
TXT;
    }

    protected function privacyDe(): string
    {
        return <<<'TXT'
Diese Datenschutzrichtlinie erläutert, wie ZoodeSIM („wir“) personenbezogene Daten erhebt, verwendet, weitergibt und schützt, wenn Sie App, Websites, Partnerportal und eSIM-Dienste nutzen.

## 1. Wer wir sind
ZoodeSIM bietet eSIM-Konnektivität, Wallet, Empfehlungsprämien und ein Partnerprogramm. Diese Richtlinie gilt für Kundenkonten, Partnerbewerbungen und Besucher der öffentlichen Seiten.

## 2. Welche Daten wir erheben
Wir erheben von Ihnen bereitgestellte und bei der Nutzung entstehende Daten:
- Name, E-Mail und Telefon
- Anmeldekennungen von Google, Apple, Facebook oder E-Mail/Passwort (einschließlich Firebase-ID)
- Gerätekennungen, IP-Adresse und ungefähre Netzlokalisierung zur Betrugsprävention
- Kaufhistorie, eSIM-Bestellungen, Wallet-Saldo und Empfehlungsaktivität
- Supportnachrichten und Untersuchungsunterlagen

## 3. Zwecke
Wir nutzen Daten, um Konten zu führen, Bestellungen zu erfüllen, das Wallet zu betreiben, Empfehlungen gutzuschreiben, Missbrauch zu erkennen und transaktionale Hinweise zu senden. Wir verkaufen Ihre Daten nicht.

## 4. Speicherung und Sicherheit
Konten, Bestellungen und Wallet werden während der aktiven Nutzung und angemessen danach aufbewahrt. Nach der Löschung ist das Konto sofort deaktiviert; Administratoren können Historie einsehen. Wir setzen Zugriffskontrollen, verschlüsselten Transport und Token-Sitzungen ein.

## 5. Ihre Rechte
Sie können Auskunft, Berichtigung oder Löschung verlangen, das Profil in der App ändern oder das Konto unter Profil → Konto löschen entfernen. Eine Verarbeitung in anderen Ländern ist möglich. ZoodeSIM richtet sich an Erwachsene.

## 6. Änderungen
Wir können diese Richtlinie aktualisieren. Die geltende Fassung steht auf dieser Seite und gegebenenfalls in der App.
TXT;
    }

    protected function privacyTg(): string
    {
        return <<<'TXT'
Ин Сиёсати махфият шарҳ медиҳад, ки ZoodeSIM («мо») чӣ гуна маълумоти шахсиро ҳангоми истифодаи барнома, сомонаҳо, панели шарик ва хидматҳои eSIM ҷамъ, истифода, мубодила ва ҳифз мекунад.

## 1. Мо кистем
ZoodeSIM пайвасти eSIM, ҳамён, мукофотҳои рефералӣ ва барномаи шариконро пешниҳод мекунад. Ин сиёсат ба ҳисобҳои муштариён, дархостҳои шарик ва меҳмонони саҳифаҳои ҷамъиятӣ дахл дорад.

## 2. Кадом маълумотро ҷамъ мекунем
Мо маълумоте, ки шумо медиҳед, ва маълумоте, ки ҳангоми истифода пайдо мешавад, ҷамъ мекунем:
- Ном, почта ва телефон
- Шиносаҳои вуруди Google, Apple, Facebook ё почта/рамз (аз ҷумла Firebase ID)
- Шиносаи дастгоҳ, IP ва ҷойгиршавии тақрибии шабака барои пешгирии қаллобӣ
- Таърихи харид, фармоишҳои eSIM, тавозуни ҳамён ва фаъолияти рефералӣ
- Паёмҳои дастгирӣ ва сабтҳои таҳқиқ

## 3. Чӣ тавр истифода мебарем
Маълумот барои сохтани ҳисоб, иҷрои фармоиш, ҳамён, рефералҳо, пешгирии сӯиистифода ва огоҳиҳои амалиётӣ аст. Мо маълумоти шахсиро намефурӯшем.

## 4. Нигоҳдорӣ ва амният
Ҳисоб, фармоиш ва ҳамён ҳангоми фаъол будани ҳисоб ва муддати зарурии баъдӣ нигоҳ дошта мешаванд. Пас аз несткунӣ ҳисоб фавран ғайрифаъол мешавад; маъмурон метавонанд таърихро нигоҳ доранд.

## 5. Имконоти шумо
Шумо метавонед дастрасӣ, ислоҳ ё несткуниро дархост кунед, профилро дар барнома навсозӣ кунед ё ҳисобра дар Профил → Нест кардани ҳисоб нест кунед. Хидмат барои калонсолон аст.

## 6. Тағйирот
Мо метавонем сиёсатро навсозӣ кунем. Нусхаи амалкунанда дар ин саҳифа нашр мешавад.
TXT;
    }

    protected function termsEn(): string
    {
        return <<<'TXT'
These Terms & Conditions govern your use of ZoodeSIM, including the mobile app, public websites, partner tools, wallet, referrals, and eSIM purchases. By creating an account or placing an order you agree to them.

## 1. The service
ZoodeSIM sells and provisions travel eSIM plans and related digital features. Coverage, speeds, and fair-use rules depend on the plan you buy and on third-party mobile networks. Plan details shown at checkout are part of the contract for that order.

## 2. Eligibility and accounts
You must be able to form a contract and provide accurate details. Keep your sign-in methods confidential. You are responsible for activity on your account, including purchases made while you are signed in.

## 3. Orders, pricing, and wallet
Prices, taxes, and currency are displayed before you confirm a purchase. Wallet credits, referral rewards, and bonuses are promotional: they have no cash value outside the app unless we say otherwise, and we may reverse them in cases of fraud, error, or abuse. Completed network provisioning is generally non-refundable except where consumer law requires otherwise.

## 4. Referrals and partners
Referral codes and partner promo codes are personal. You must not self-refer, share codes in a way that breaks programme rules, or create duplicate accounts to obtain rewards. We may withhold or reclaim rewards when we reasonably suspect misuse.

## 5. Acceptable use
You agree not to:
- Use the service for unlawful, harmful, or fraudulent activity
- Interfere with networks, provisioning, or other customers
- Reverse engineer, scrape, or overload our APIs except as allowed
- Bypass fraud, device, or account limits

We may suspend or deactivate accounts that violate these rules.

## 6. Availability and networks
eSIM connectivity is provided by third-party operators. We do not guarantee uninterrupted coverage, particular speeds, or compatibility with every device. Outages, roaming restrictions, and local regulations are outside our reasonable control.

## 7. Liability
To the fullest extent permitted by law, ZoodeSIM is not liable for indirect, incidental, or consequential losses, or for network failures of third parties. Our total liability for a claim relating to a purchase is limited to the amount you paid for that purchase, except where liability cannot be limited.

## 8. Changes
We may update plans, pricing, and these terms. Material changes will be published on this page. If you continue to use ZoodeSIM after an update, the new terms apply to later use and orders.

## 9. Contact
Questions about these terms can be sent through in-app support. An administrator may update this published text. It is provided for operational clarity and is not legal advice.
TXT;
    }

    protected function termsEs(): string
    {
        return <<<'TXT'
Estos Términos rigen el uso de ZoodeSIM, incluida la app, sitios públicos, herramientas de socios, monedero, referidos y compras eSIM. Al crear una cuenta o pedir, los aceptas.

## 1. El servicio
ZoodeSIM vende y provisiona planes eSIM de viaje. Cobertura, velocidad y uso justo dependen del plan y de redes de terceros. El detalle en el pago forma parte del contrato de ese pedido.

## 2. Cuentas
Debes poder contratar y dar datos exactos. Protege tus accesos. Eres responsable de la actividad de tu cuenta.

## 3. Pedidos y monedero
Precios y moneda se muestran antes de confirmar. Créditos, referidos y bonos son promocionales y pueden revertirse por fraude, error o abuso. El aprovisionamiento completado suele no ser reembolsable salvo cuando la ley lo exija.

## 4. Referidos
Los códigos son personales. No te auto-refieras ni crees cuentas duplicadas. Podemos retener o recuperar recompensas ante un uso indebido razonable.

## 5. Uso aceptable
No uses el servicio de forma ilícita, no interfieras con redes ni eludas límites de fraude. Podemos suspender cuentas que incumplan estas reglas.

## 6. Disponibilidad y responsabilidad
La conectividad depende de operadores terceros. No garantizamos cobertura continua ni compatibilidad con todos los dispositivos. En la medida legal, no respondemos por daños indirectos ni fallos de red ajenos; la responsabilidad por una compra se limita a lo pagado por esa compra, salvo límites inderogables.

## 7. Cambios
Podemos actualizar planes, precios y estos términos. El texto vigente se publica aquí.
TXT;
    }

    protected function termsRu(): string
    {
        return <<<'TXT'
Настоящие Условия регулируют использование ZoodeSIM: приложения, сайтов, кабинета партнёра, кошелька, рефералов и покупок eSIM. Создавая аккаунт или оформляя заказ, вы их принимаете.

## 1. Сервис
ZoodeSIM продаёт и выпускает travel-eSIM. Покрытие и скорость зависят от тарифа и сетей партнёров. Описание на оплате входит в договор по этому заказу.

## 2. Аккаунт
Указывайте достоверные данные и храните доступ в тайне. Вы отвечаете за действия в аккаунте.

## 3. Заказы и кошелёк
Цена видна до подтверждения. Бонусы кошелька и рефералы — акционные и могут быть отозваны при мошенничестве, ошибке или злоупотреблении. После выпуска профиля возврат, как правило, не производится, кроме случаев, предусмотренных законом.

## 4. Рефералы
Коды персональны. Запрещены самореферал и дублирующие аккаунты. Мы можем удержать или вернуть награды при обоснованном подозрении в злоупотреблении.

## 5. Допустимое использование
Запрещены незаконная деятельность, вмешательство в сети и обход антифрода. Нарушение может привести к блокировке.

## 6. Доступность и ответственность
Связь обеспечивают сторонние операторы. Мы не гарантируем непрерывное покрытие. В пределах закона мы не отвечаем за косвенные убытки и сбои чужих сетей; ответственность по покупке ограничена суммой этой покупки.

## 7. Изменения
Тарифы, цены и условия могут обновляться. Актуальная редакция публикуется на этой странице.
TXT;
    }

    protected function termsFr(): string
    {
        return <<<'TXT'
Ces conditions régissent l’usage de ZoodeSIM (application, sites, outils partenaires, portefeuille, parrainage et achats eSIM). En créant un compte ou en commandant, vous les acceptez.

## 1. Le service
ZoodeSIM vend et provisionne des forfaits eSIM voyage. Couverture et débit dépendent du forfait et des réseaux tiers. Le détail au paiement fait partie du contrat de cette commande.

## 2. Compte
Fournissez des informations exactes et protégez vos identifiants. Vous êtes responsable de l’activité du compte.

## 3. Commandes et portefeuille
Les prix s’affichent avant confirmation. Crédits, parrainages et bonus sont promotionnels et peuvent être repris en cas de fraude, d’erreur ou d’abus. Un profil déjà provisionné n’est en général pas remboursable, sauf obligation légale.

## 4. Parrainage
Les codes sont personnels. L’auto-parrainage et les comptes multiples pour des récompenses sont interdits. Nous pouvons retenir ou récupérer des avantages en cas d’usage abusif.

## 5. Usage acceptable
Pas d’activité illicite, d’atteinte aux réseaux ni de contournement anti-fraude. Un manquement peut entraîner une suspension.

## 6. Disponibilité et responsabilité
La connectivité relève d’opérateurs tiers. Nous ne garantissons pas une couverture continue. Dans les limites légales, nous ne sommes pas responsables des dommages indirects ni des pannes de réseaux tiers ; la responsabilité pour un achat est limitée au montant payé.

## 7. Modifications
Forfaits, prix et conditions peuvent évoluer. La version en vigueur est publiée ici.
TXT;
    }

    protected function termsDe(): string
    {
        return <<<'TXT'
Diese Bedingungen gelten für ZoodeSIM (App, Websites, Partnertools, Wallet, Empfehlungen und eSIM-Käufe). Mit Kontoerstellung oder Bestellung stimmen Sie zu.

## 1. Der Dienst
ZoodeSIM verkauft und provisioniert Reise-eSIMs. Abdeckung und Geschwindigkeit hängen vom Tarif und von Drittnetzen ab. Die Angaben an der Kasse sind Vertragsbestandteil dieser Bestellung.

## 2. Konto
Angaben müssen zutreffen; Zugangsdaten sind geheim zu halten. Sie verantworten die Kontoaktivität.

## 3. Bestellungen und Wallet
Preise erscheinen vor der Bestätigung. Guthaben, Empfehlungen und Boni sind werblich und können bei Betrug, Irrtum oder Missbrauch storniert werden. Bereits provisionierte Profile sind in der Regel nicht erstattungsfähig, soweit das Recht nichts anderes verlangt.

## 4. Empfehlungen
Codes sind persönlich. Selbstwerbung und Doppelkonten für Prämien sind unzulässig. Bei Missbrauch können wir Prämien einbehalten oder zurückfordern.

## 5. Zulässige Nutzung
Keine rechtswidrige Nutzung, keine Netzstörungen, kein Umgehen von Betrugslimits. Verstöße können zur Sperre führen.

## 6. Verfügbarkeit und Haftung
Konnektivität liefern Drittanbieter. Eine durchgehende Abdeckung wird nicht garantiert. Soweit zulässig haften wir nicht für indirekte Schäden oder fremde Netzausfälle; die Haftung für einen Kauf ist auf den gezahlten Betrag begrenzt.

## 7. Änderungen
Tarife, Preise und Bedingungen können aktualisiert werden. Die geltende Fassung steht auf dieser Seite.
TXT;
    }

    protected function termsTg(): string
    {
        return <<<'TXT'
Ин Шартҳо истифодаи ZoodeSIM-ро танзим мекунанд: барнома, сомонаҳо, абзорҳои шарик, ҳамён, рефералҳо ва хариди eSIM. Бо сохтани ҳисоб ё фармоиш шумо онҳоро қабул мекунед.

## 1. Хидмат
ZoodeSIM нақшаҳои eSIMи сафарро мефурӯшад ва фаъол мекунад. Фарогирӣ ва суръат аз тариф ва шабакаҳои шарик вобастаанд.

## 2. Ҳисоб
Маълумоти дуруст диҳед ва вурудро ҳифз кунед. Шумо барои фаъолияти ҳисоб масъул ҳастед.

## 3. Фармоиш ва ҳамён
Нарх пеш аз тасдиқ нишон дода мешавад. Кредитҳо ва мукофотҳо таблиғотӣ мебошанд ва ҳангоми қаллобӣ, хато ё сӯиистифода метавонанд бозпас гирифта шаванд.

## 4. Рефералҳо
Рамзҳо шахсӣ мебошанд. Худ-реферал ва ҳисобҳои такрорӣ манъ аст.

## 5. Истифодаи қобили қабул
Фаъолияти ғайриқонунӣ, халал ба шабака ва убури ҳадди қаллобӣ манъ аст. Ҳисоб метавонад қатъ карда шавад.

## 6. Дастрасӣ ва масъулият
Пайвастшавӣ аз операторҳои шарик аст. Фарогирии бефосила кафолат дода намешавад. Дар ҳудуди қонун мо барои зарари ғайримустақим масъул нестем.

## 7. Тағйирот
Тарифҳо ва шартҳо метавонанд нав шаванд. Нусхаи амалкунанда дар ин саҳифа аст.
TXT;
    }

    protected function deleteEn(): string
    {
        return <<<'TXT'
You can close your ZoodeSIM customer account from the mobile app. This page explains what happens so you can review the process before you confirm.

## 1. How to delete
Open the ZoodeSIM app, go to Profile, and choose Delete account. Confirm the prompt. Deletion is performed by the signed-in account over a secure API; we do not delete accounts from this website.

## 2. What happens immediately
After you confirm:
- The account is deactivated (soft-deleted) and hidden from the app
- All app sessions and API tokens are revoked
- You cannot sign in with email/password or with Google, Apple, Facebook, or Firebase Email/Password for that account

## 3. What we keep
Purchase history, wallet ledgers, eSIM orders, and referral records remain available to ZoodeSIM administrators for accounting, fraud prevention, support, and legal duties. We do not restore a deleted account from this page.

## 4. Email and sign-in reuse
The same email address and the same social or Firebase identity cannot be used to create a new ZoodeSIM user. This prevents duplicate rewards and protects existing orders.

## 5. eSIM plans already issued
Deleting the account does not automatically cancel connectivity that was already provisioned to your device. If you need help with an active plan, contact support before you delete.

## 6. Questions
If you cannot access the app, contact support from the channels listed in the store listing or partner communications. This page is provided to meet app-store account-deletion requirements and to describe our current process.
TXT;
    }

    protected function deleteEs(): string
    {
        return <<<'TXT'
Puedes cerrar tu cuenta ZoodeSIM desde la app. Esta página explica el proceso antes de confirmar.

## 1. Cómo eliminar
Abre la app, ve a Perfil y elige Eliminar cuenta. Confirma. La eliminación la hace la cuenta autenticada por API segura; no borramos cuentas desde este sitio.

## 2. Qué ocurre de inmediato
Tras confirmar, la cuenta se desactiva, se cierran sesiones y tokens, y no podrás entrar con correo ni con Google, Apple, Facebook o Firebase.

## 3. Qué conservamos
Pedidos, monedero, eSIM y referidos siguen visibles para administradores por contabilidad, fraude, soporte y ley. No restauramos la cuenta desde aquí.

## 4. Reutilización
No podrás registrar de nuevo el mismo correo ni la misma identidad social o Firebase.

## 5. Planes eSIM ya emitidos
Borrar la cuenta no cancela sola la conectividad ya provisionada. Si necesitas ayuda con un plan activo, contacta a soporte antes de eliminar.

## 6. Preguntas
Si no puedes abrir la app, usa los canales de soporte publicados. Esta página cumple requisitos de las tiendas de aplicaciones.
TXT;
    }

    protected function deleteRu(): string
    {
        return <<<'TXT'
Закрыть аккаунт ZoodeSIM можно в приложении. Ниже описан процесс до подтверждения.

## 1. Как удалить
Откройте приложение, Профиль → Удалить аккаунт и подтвердите. Удаление выполняет авторизованный аккаунт через защищённый API; с этого сайта аккаунты не удаляются.

## 2. Что происходит сразу
Аккаунт деактивируется, сессии и токены отзываются, вход по email и через Google, Apple, Facebook или Firebase становится невозможным.

## 3. Что сохраняется
Заказы, кошелёк, eSIM и рефералы остаются у администраторов для учёта, антифрода, поддержки и закона. Восстановление с этой страницы не предусмотрено.

## 4. Повторная регистрация
Тот же email и та же социальная или Firebase-личность не могут создать нового пользователя.

## 5. Уже выпущенные eSIM
Удаление аккаунта само по себе не отключает уже выпущенный профиль. По активному тарифу обратитесь в поддержку заранее.

## 6. Вопросы
Если нет доступа к приложению, используйте опубликованные каналы поддержки. Страница нужна в том числе для требований магазинов приложений.
TXT;
    }

    protected function deleteFr(): string
    {
        return <<<'TXT'
Vous pouvez fermer votre compte ZoodeSIM dans l’application. Cette page décrit le processus avant confirmation.

## 1. Comment supprimer
Ouvrez l’app, Profil → Supprimer le compte, puis confirmez. La suppression est faite par le compte connecté via une API sécurisée ; ce site ne supprime pas les comptes.

## 2. Effet immédiat
Le compte est désactivé, les sessions et jetons sont révoqués, la connexion e-mail, Google, Apple, Facebook ou Firebase devient impossible.

## 3. Ce que nous conservons
Commandes, portefeuille, eSIM et parrainages restent visibles aux administrateurs pour la comptabilité, la fraude, le support et la loi. Pas de restauration depuis cette page.

## 4. Réutilisation
Le même e-mail et la même identité sociale ou Firebase ne peuvent pas créer un nouvel utilisateur.

## 5. Forfaits déjà émis
La suppression n’annule pas à elle seule une eSIM déjà provisionnée. Contactez le support avant de supprimer si un forfait est actif.

## 6. Questions
Sans accès à l’app, utilisez les canaux d’assistance publiés. Cette page répond aussi aux exigences des stores.
TXT;
    }

    protected function deleteDe(): string
    {
        return <<<'TXT'
Sie können Ihr ZoodeSIM-Konto in der App schließen. Diese Seite beschreibt den Ablauf vor der Bestätigung.

## 1. So löschen Sie
Öffnen Sie die App, Profil → Konto löschen, und bestätigen Sie. Die Löschung erfolgt durch das angemeldete Konto über eine sichere API; diese Website löscht keine Konten.

## 2. Sofortige Wirkung
Das Konto wird deaktiviert, Sitzungen und Tokens werden widerrufen, eine Anmeldung per E-Mail, Google, Apple, Facebook oder Firebase ist nicht mehr möglich.

## 3. Was wir behalten
Bestellungen, Wallet, eSIM und Empfehlungen bleiben für Administratoren aus Buchhaltungs-, Betrugs-, Support- und Rechtsgründen sichtbar. Eine Wiederherstellung über diese Seite gibt es nicht.

## 4. Erneute Registrierung
Dieselbe E-Mail und dieselbe Social- oder Firebase-Identität können kein neues Nutzerkonto anlegen.

## 5. Bereits ausgegebene eSIMs
Das Löschen beendet nicht automatisch bereits provisionierte Verbindung. Bei einem aktiven Tarif wenden Sie sich vorher an den Support.

## 6. Fragen
Ohne App-Zugang nutzen Sie die veröffentlichten Supportkanäle. Diese Seite erfüllt auch Anforderungen der App-Stores.
TXT;
    }

    protected function deleteTg(): string
    {
        return <<<'TXT'
Шумо метавонед ҳисоби ZoodeSIM-ро аз барнома пӯшед. Ин саҳифа равандро пеш аз тасдиқ шарҳ медиҳад.

## 1. Чӣ тавр нест кардан
Барномаро кушоед, Профил → Нест кардани ҳисоб ва тасдиқ кунед. Несткунӣ аз ҳисоби воридшуда тавассути API-и бехатар аст; аз ин сомона ҳисоб нест намешавад.

## 2. Фавран чӣ мешавад
Ҳисоб ғайрифаъол мешавад, ҷаласаҳо ва токенҳо бекор мешаванд, вуруд бо почта, Google, Apple, Facebook ё Firebase имконнопазир мегардад.

## 3. Чӣ боқӣ мемонад
Фармоиш, ҳамён, eSIM ва рефералҳо барои маъмурон боқӣ мемонанд. Барқарорсозӣ аз ин саҳифа нест.

## 4. Сабти дубора
Ҳамон почта ва ҳамон ҳувияти иҷтимоӣ ё Firebase барои ҳисоби нав истифода намешавад.

## 5. Нақшаҳои аллакай додашуда
Несткунии ҳисоб пайвасти аллакай фаъолшударо худкор қатъ намекунад. Пеш аз несткунӣ ба дастгирӣ муроҷиат кунед.

## 6. Саволҳо
Агар барнома дастрас набошад, каналҳои дастгирии нашршударо истифода баред.
TXT;
    }
}
