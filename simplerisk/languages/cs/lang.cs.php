<?php
/***********************************************************************
 * This Source Code Form is subject to the terms of the Mozilla Public *
 * License, v. 2.0. If a copy of the MPL was not distributed with this *
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.            *
 ***********************************************************************/

ini_set('default_charset', 'utf-8');
define('DATETIME', 'Y-m-d g:i A T');
define('DATETIMESIMPLE', 'Y-m-d H:i');
define('DATESIMPLE', 'Y-m-d');

$lang = array(
    'Home'=>'Domů',
    'RiskManagement'=>'Řízení Rizik',
    'Reporting'=>'Hlášení',
    'Configure'=>'Konfigurace',
    'MyProfile'=>'Můj Profil',
    'Logout'=>'Odhlášení',
    'LogInHere'=>'Přihlášení do SimpleRisk',
    'Username'=>'Uživatelské jméno',
    'Password'=>'Heslo',
    'ForgotYourPassword'=>'Zapomněli jste heslo',
    'Login'=>'Přihlášení',
    'Reset'=>'Obnovit',
    'Send'=>'Poslat',
    'Update'=>'Aktualizace',
    'SendPasswordResetEmail'=>'Odeslat Email Na Obnovu Hesla',
    'PasswordReset'=>'Resetování Hesla',
    'ResetToken'=>'Resetovat Tokenu',
    'RepeatPassword'=>'Opakujte Heslo',
    'Submit'=>'Předložit',
    'ProfileDetails'=>'Detaily V Profilu',
    'LastLogin'=>'Poslední Přihlášení',
    'ChangePassword'=>'Změnit Heslo',
    'CurrentPassword'=>'Aktuální Heslo',
    'NewPassword'=>'Nové Heslo',
    'ConfirmPassword'=>'Potvrdit Heslo',
    'ConfigureRiskFormula'=>'Konfigurovat Riziko Vzorec',
    'ConfigureReviewSettings'=>'Konfigurovat Nastavení Recenzi',
    'AddAndRemoveValues'=>'Přidání a Odebrání Hodnot',
    'UserManagement'=>'Správa Uživatelů',
    'RedefineNamingConventions'=>'Definovat Konvence Pojmenování',
    'AuditTrail'=>'Revizní záznam',
    'Extras'=>'Doplňky',
    'Announcements'=>'Oznámení',
    'About'=>'O',
    'Impact'=>'Dopad',
    'Likelihood'=>'Pravděpodobnost',
    'MitigationEffort'=>'Úsilí O Zmírňování',
    'Change'=>'Změnit',
    'to'=>'k',
    'AddANewUser'=>'Přidat Nového Uživatele',
    'Type'=>'Typ',
    'FullName'=>'Celé Jméno',
    'EmailAddress'=>'E-mailovou Adresu',
    'Teams'=>'Tým(y)',
    'ALL'=>'VŠECHNY',
    'NONE'=>'NIC',
    'UserResponsibilities'=>'Uživatelská Povinnosti',
    'AbleToSubmitNewRisks'=>'Schopni Předložit Nová Rizika',
    'AbleToModifyExistingRisks'=>'Možnost Upravit Existující Rizika',
    'AbleToCloseRisks'=>'Schopen Uzavřít Rizika',
    'AbleToPlanMitigations'=>'Možnost Plánovat skutečnosti snižující závažnost rizika',
    'AbleToReviewLowRisks'=>'Možnost Přezkoumat Nízká Rizika',
    'AbleToReviewMediumRisks'=>'Možnost Přezkoumat Střední Rizika',
    'AbleToReviewHighRisks'=>'Možnost Přezkoumat Vysoká Rizika',
    'AllowAccessToConfigureMenu'=>'Povolit Přístup k "Nastavení" Menu',
    'MultiFactorAuthentication'=>'Multi-Faktor Autentizace',
    'None'=>'Nic',
    'Add'=>'Přidat',
    'ViewDetailsForUser'=>'Zobrazit Detaily pro Uživatele',
    'DetailsForUser'=>'Informace pro Uživatele',
    'Select'=>'Vyberte',
    'EnableAndDisableUsers'=>'Povolit nebo Zakázat Uživatelům',
    'EnableAndDisableUsersHelp'=>'Pomocí této funkce můžete povolit nebo zakázat přihlášení uživatele při zachování auditní stopy uživatelských aktivit',
    'DisableUser'=>'Zakázat uživatele',
    'Disable'=>'Zakázat',
    'EnableUser'=>'Povolit uživatele',
    'Enable'=>'Povolit',
    'DeleteAnExistingUser'=>'Odstranit Existující Uživatele',
    'DeleteCurrentUser'=>'Smazat aktuálního uživatele',
    'Delete'=>'Odstranit',
    'SendPasswordResetEmailForUser'=>'Odeslat email na obnovu hesla pro uživatele',
    'Category'=>'Kategorie',
    'AddNewCategoryNamed'=>'Přidat nové kategorie s názvem',
    'DeleteCurrentCategoryNamed'=>'Odstranit stávající kategorie s názvem',
    'Team'=>'Tým',
    'AddNewTeamNamed'=>'Přidat nový tým jménem',
    'DeleteCurrentTeamNamed'=>'Odstranit současný tým jménem',
    'Technology'=>'Technologie',
    'AddNewTechnologyNamed'=>'Přidat novou technologii s názvem',
    'DeleteCurrentTechnologyNamed'=>'Odstranit stávající technologie jménem',
    'SiteLocation'=>'Místo/Location',
    'AddNewSiteLocationNamed'=>'Přidat nové stránky/umístění jménem',
    'DeleteCurrentSiteLocationNamed'=>'Odstranit aktuální stránky/umístění jménem',
    'ControlRegulation'=>'Nařízení O Kontrole',
    'AddNewControlRegulationNamed'=>'Přidat nové nařízení o kontrole jménem',
    'DeleteCurrentControlRegulationNamed'=>'Odstranit stávající nařízení o kontrole jménem',
    'RiskPlanningStrategy'=>'Rizik, Plánování, Strategie',
    'AddNewRiskPlanningStrategyNamed'=>'Přidat nové rizik, plánování strategie jménem',
    'DeleteCurrentRiskPlanningStrategyNamed'=>'Odstranit aktuální rizika plánování strategie jménem',
    'CloseReason'=>'V Blízkosti Důvod, Proč',
    'AddNewCloseReasonNamed'=>'Přidat nové úzké důvod jménem',
    'DeleteCurrentCloseReasonNamed'=>'Odstranit aktuální blízko důvod jménem',
    'IWantToReviewHighRiskEvery'=>'Chci, aby přezkoumala VYSOKÉ riziko každý',
    'IWantToReviewMediumRiskEvery'=>'Chci k recenzi STŘEDNÍ riziko každý',
    'IWantToReviewLowRiskEvery'=>'Chci recenzi NÍZKÉ riziko každý',
    'days'=>'dny',
    'MyClassicRiskFormulaIs'=>'Můj Klasický Riziko Vzorec Je',
    'RISK'=>'RIZIKO',
    'IConsiderHighRiskToBeAnythingGreaterThan'=>'Považuji za VYSOKÉ riziko být něco větší, než',
    'IConsiderMediumRiskToBeLessThanAboveButGreaterThan'=>'Považuji za STŘEDNÍ riziko musí být menší než výše uvedené, ale větší než',
    'IConsiderlowRiskToBeLessThanAboveButGreaterThan'=>'Považuji NÍZKÉ riziko méně než výše, ale větší než',
    'HighRisk'=>'Vysoké Riziko',
    'MediumRisk'=>'Střední Riziko',
    'LowRisk'=>'Nízké Riziko',
    'Irrelevant'=>'Irelevantní',
    'SubmitYourRisks'=>'Odeslat riziko',
    'PlanYourMitigations'=>'Plán Zmírnění',
    'PerformManagementReviews'=>'Provést hodnocení',
    'PrioritizeForProjectPlanning'=>'Plánování projektů',
    'ReviewRisksRegularly'=>'Pravidelně',
    'DocumentANewRisk'=>'Dokument Nové Riziko',
    'UseThisFormHelp'=>'Pomocí tohoto formuláře za účelem dokumentu nového rizika ke zvážení v Procesu Řízení Rizik',
    'Subject'=>'Předmět',
    'ExternalReferenceId'=>'Externí Referenční ID',
    'ControlNumber'=>'Kontrolní Číslo',
    'Owner'=>'Majitel',
    'OwnersManager'=>'Správce vlastníka',
    'RiskScoringMethod'=>'Hodnocení Rizik Metoda',
    'CurrentLikelihood'=>'Aktuální Pravděpodobnost',
    'CurrentImpact'=>'Aktuální Dopad',
    'RiskAssessment'=>'Posouzení Rizik',
    'AdditionalNotes'=>'Další Poznámky',
    'UNREVIEWED'=>'NEZAŘAZENÉ',
    'PASTDUE'=>'PO SPLATNOSTI',
    'ID'=>'ID',
    'Status'=>'Stav',
    'Risk'=>'Riziko',
    'DaysOpen'=>'Dny Otevřených',
    'CalculatedRisk'=>'Vypočítané Riziko',
    'SubmittedBy'=>'Předložený',
    'NextReviewDate'=>'Další Recenze Datum',
    'CVSSRiskScoring'=>'CVSS Riziko Bodování',
    'DREADRiskScoring'=>'STRACH Riziko Bodování',
    'OWASPRiskScoring'=>'OWASP Risk Bodování',
    'CustomRiskScoring'=>'Vlastní Hodnocení Rizik',
    'MitigationPlanningHelp'=>'Níže je seznam předložených rizik, která vyžadují zmírnění plánování',
    'ManagementReviewHelp'=>'Níže je seznam předložených rizika, která vyžadují řízení recenzi',
    'Submitted'=>'Předložené',
    'MitigationPlanned'=>'Zmírnění Plánované',
    'ManagementReview'=>'Přezkoumání Vedením',
    'No'=>'Ne',
    'Yes'=>'Ano',
    'AddAndRemoveProjects'=>'Přidat a Odebrat Projektů',
    'AddAndRemoveProjectsHelp'=>'Přidat a odebrat projektů s cílem spojit více rizik společně pro stanovení priorit',
    'AddNewProjectNamed'=>'Přidat nový projekt s názvem',
    'DeleteCurrentProjectNamed'=>'Odstranit aktuální projekt s názvem',
    'AddUnassignedRisksToProjects'=>'Přidat Nepřiřazené Rizika Projektů',
    'AddUnassignedRisksToProjectsHelp'=>'Drag and drop nepřiřazené rizik označeny za úplatu jako projekt do příslušného projektu tab',
    'PrioritizeProjects'=>'Priority Projektů',
    'PrioritizeProjectsHelp'=>'Projekty kolem a změnit pořadí priorit. Jakmile budete hotovi, nezapomeňte stisknout tlačítko "Update" pro uložení změn',
    'SaveRisksToProjects'=>'Uložit Rizik pro Projekty',
    'RiskId'=>'Riziko ID',
    'RiskActions'=>'Akce',
    'ReopenRisk'=>'Znovu Riskovat',
    'CloseRisk'=>'Blízko Riziko',
    'EditRisk'=>'Edit Riziko',
    'PlanAMitigation'=>'Plán na Zmírnění',
    'PerformAReview'=>'Provádět celou Recenzi',
    'AddAComment'=>'Přidat Komentář',
    'ShowRiskScoringDetails'=>'Zobrazit rizika podrobnosti o skórování',
    'HideRiskScoringDetails'=>'Skrýt Riziko Bodování Podrobnosti',
    'Details'=>'Podrobnosti',
    'SubmissionDate'=>'Mezní Termín',
    'DateSubmitted'=>'Datum Předložení',
    'EditDetails'=>'Upravit Detaily',
    'Mitigation'=>'Ke zmírnění',
    'MitigationDate'=>'Datum předložení ke zmírnění',
    'PlanningStrategy'=>'Plánování Strategie',
    'CurrentSolution'=>'Aktuální Řešení',
    'SecurityRequirements'=>'Bezpečnostní Požadavky',
    'SecurityRecommendations'=>'Bezpečnostní Doporučení',
    'EditMitigation'=>'Edit Zmírnění',
    'LastReview'=>'Poslední Recenze',
    'ReviewDate'=>'Recenze Datum',
    'Reviewer'=>'Recenzent',
    'Review'=>'Recenze',
    'NextStep'=>'Další Krok',
    'Comments'=>'Komentáře',
    'ViewAllReviews'=>'Zobrazit Všechny Recenze',
    'ReviewHistory'=>'Recenze Historie',
    'Comment'=>'Komentář',
    'ClassicRiskScoring'=>'Klasické Hodnocení Rizik',
    'RiskScoringActions'=>'Riziko Bodování Akce',
    'UpdateClassicScore'=>'Aktualizace Klasické Skóre',
    'ScoreBy'=>'Skóre',
    'RISKClassicExp1'=>'RIZIKO = ( Pravděpodobnost x Dopad + 2(Dopad) )',
    'RISKClassicExp2'=>'RIZIKO = ( Pravděpodobnost x Dopad + Vliv )',
    'RISKClassicExp3'=>'RIZIKO = ( Pravděpodobnost x Dopad )',
    'RISKClassicExp4'=>'RIZIKO = ( Pravděpodobnost x Dopad + Pravděpodobnost )',
    'RISKClassicExp5'=>'RIZIKO = ( Pravděpodobnost x Dopad + 2(Pravděpodobnost) )',
    'Reason'=>'Důvod',
    'CloseOutInformation'=>'Close-Out Informace',
    'SubmitManagementReview'=>'Předložit Vedení Recenzi',
    'SubmitRiskMitigation'=>'Předložit Ke Zmírnění Rizika',
    'RiskDashboard'=>'Riziko Dashboard',
    'RiskTrend'=>'Riziko Trend',
    'AllOpenRisksAssignedToMeByRiskLevel'=>'Všechny otevřené rizika přiřazená mně',
    'AllOpenRisksByRiskLevel'=>'Všechny Otevřené Rizika Úroveň Rizika',
    'AllOpenRisksConsideredForProjectsByRiskLevel'=>'Všechny Otevřené Rizik Považovány za Projekty podle Úrovně Rizika',
    'AllOpenRisksAcceptedUntilNextReviewByRiskLevel'=>'Všechny Otevřené Rizika Přijímány Do Dalšího Přezkumu podle Úrovně Rizika',
    'AllOpenRisksToSubmitAsAProductionIssueByRiskLevel'=>'Všechny Otevřené Rizik Předložit jako Produkční Vydání podle Úrovně Rizika',
    'AllOpenRisksByScoringMethod'=>'Všechny Otevřené Rizik pomocí Skórovací Metody',
    'AllClosedRisksByRiskLevel'=>'Všechny Uzavřené Rizika Úroveň Rizika',
    'SubmittedRisksByDate'=>'Předložené Rizika Podle Data',
    'MitigationsByDate'=>'Skutečnosti Snižující Závažnost Rizika Podle Data',
    'ManagementReviewsByDate'=>'Řízení Podle Data Recenze',
    'ProjectsAndRisksAssigned'=>'Projekty a Rizika Přiřazena',
    'OpenRisks'=>'Otevřete Rizika',
    'ClosedRisks'=>'Uzavřené Rizika',
    'ReportMyOpenHelp'=>'Tato zpráva ukazuje všechny otevřené rizik, která mají aktuálního uživatele jako vlastníka nebo správce spojené s rizikem seřazeny podle úrovně rizika',
    'ReportOpenHelp'=>'Tato zpráva ukazuje všechny otevřené rizik, seřazené podle úrovně rizika',
    'ReportProjectsHelp'=>'Tato zpráva ukazuje všechny otevřené rizik považovány za projekty seřazeny podle úrovně rizika',
    'ReportNextReviewHelp'=>'Tato zpráva ukazuje všechny otevřené rizika přijímány do dalšího přezkumu, seřazené podle úrovně rizika',
    'ReportProductionIssuesHelp'=>'Tato zpráva ukazuje všechny otevřené rizik předložen jako výrobní problémy, seřazené podle úrovně rizika',
    'ReportRiskScoringHelp'=>'Tato zpráva se zobrazí všechny hodnocení rizik, metody a rizika skóroval pomocí každý',
    'ReportClosedHelp'=>'Tato zpráva ukazuje, že všechny uzavřené rizika seřazeny podle úrovně rizika',
    'ReportSubmittedByDateHelp'=>'Tato zpráva ukazuje všechna rizika seřazeny podle data podání,',
    'ReportMitigationsByDateHelp'=>'Tato zpráva ukazuje všechny skutečnosti snižující závažnost rizika plánované nařídil zmírnění datum',
    'ReportMgmtReviewsByDateHelp'=>'Tato zpráva ukazuje všechny správy recenze nařídil recenze datum',
    'ReportProjectsAndRisksHelp'=>'Tato zpráva se zobrazí všechny projekty a rizika, které jim',
    'Language'=>'Jazyk',
    'AllOpenRisksNeedingReview'=>'Všechny Otevřené Rizika Nutnosti Recenzi',
    'ReportReviewNeededHelp'=>'Tato zpráva ukazuje všechny otevřené rizika nutnosti přezkoumání vedením',
    'CustomValue'=>'Vlastní Hodnota',
    'ClosedRisksByDate'=>'Uzavřené Rizika Podle Data',
    'DateClosed'=>'Datum Uzavření',
    'ClosedBy'=>'Uzavřen',
    'ReportClosedByDateHelp'=>'Tato zpráva ukazuje všechna rizika nařídil uzavření datum',
    'AllOpenRisksByTeam'=>'Všechny Otevřené Rizika tím, že Tým',
    'ReportRiskTeamsHelp'=>'Tato zpráva ukazuje všechny týmy a rizika přiřazena ke každému',
    'Unassigned'=>'Nepřiřazené',
    'AllOpenRisksByTechnology'=>'Všechny Otevřené Rizik Podle Technologie',
    'ReportRiskTechnologiesHelp'=>'Tato zpráva ukazuje všechny technologie a rizika přiřazena ke každému',
    'RiskLevel'=>'Úroveň Rizika',
    'BasedOnTheCurrentRiskScore'=>'Na základě své skóre rizika dalšího přezkumu bude ',
    'WouldYouLikeToUseADifferentDate'=>'Chtěli byste použít jiné datum, místo?',
    'RisksOpenedAndClosedOverTime'=>'Rizika Otevřeny a Uzavřeny v Průběhu Času',
    'AllRiskScoresAreAdjusted'=>'Všechny rizikové skóre jsou upraveny, aby se vešly na škále 0-10.',
    'DetermineProjectStatus'=>'Určení Stavu Projektu',
    'ProjectStatusHelp'=>'Místo projektů do kbelíků na základě jejich aktuálního stavu.',
    'ActiveProjects'=>'Aktivní Projekty',
    'OnHoldProjects'=>'Na Držení Projektů',
    'CompletedProjects'=>'Dokončené Projekty',
    'CancelledProjects'=>'Zrušené Projekty',
    'UpdateProjectStatuses'=>'Aktualizace Statusů Projektu',
    'HighRiskReport'=>'Vysoce Rizikové Zprávy',
    'TotalOpenRisks'=>'Celkové Otevřít Rizika',
    'TotalHighRisks'=>'Celkem Vysoká Rizika',
    'HighRiskPercentage'=>'Vysoké Riziko Procento',
    'UpdateClassicScore'=>'Aktualizace klasické skóre',
    'CurrentLikelihood'=>'Aktuální pravděpodobnost',
    'CurrentImpact'=>'Současný dopad',   
    'UpdateCVSSScore'=>'Aktualizace CVSS Skóre',
    'BaseScoreMetrics'=>'Základní Skóre Metriky',
    'AttackVector'=>'Útok Vektor',
    'AttackComplexity'=>'Složitost Útoku',
    'Authentication'=>'Ověřování',
    'ConfidentialityImpact'=>'Důvěrnost Dopad',
    'IntegrityImpact'=>'Integrita Dopad',
    'AvailabilityImpact'=>'Dostupnost Dopad',
    'TemporalScoreMetrics'=>'Časové Skóre Metriky',
    'Exploitability'=>'Využitelnosti',
    'RemediationLevel'=>'Sanace Úrovni',
    'ReportConfidence'=>'Zpráva Důvěru',
    'EnvironmentalScoreMetrics'=>'Environmentální Skóre Metriky',
    'CollateralDamagePotential'=>'Potenciální Vedlejší Škody',
    'TargetDistribution'=>'Cíl Distribuce',
    'ConfidentialityRequirement'=>'Požadavek Důvěrnosti',
    'IntegrityRequirement'=>'Požadavek Integrity',
    'AvailabilityRequirement'=>'Dostupnost Požadavek',
    'UpdateDREADScore'=>'Aktualizace STRACH Skóre',
    'DamagePotential'=>'Poškození',
    'Reproducibility'=>'Reprodukovatelnost',
    'AffectedUsers'=>'Postižených Uživatelů',
    'Discoverability'=>'Rozpoznání',
    'UpdateOWASPScore'=>'Aktualizace OWASP Skóre',
    'ThreatAgentFactors'=>'Nebezpečný Agent Faktory',
    'SkillLevel'=>'Úroveň Dovedností',
    'Motive'=>'Motiv',
    'Opportunity'=>'Příležitost',
    'Size'=>'Velikost',
    'VulnerabilityFactors'=>'Zranitelnost Faktory',
    'EaseOfDiscovery'=>'Snadné Objev',
    'EaseOfExploit'=>'Snadné Zneužít',
    'Awareness'=>'Povědomí',
    'IntrusionDetection'=>'Detekce Narušení',
    'TechnicalImpact'=>'Technické Dopad',
    'LossOfConfidentiality'=>'Ztráta Důvěrnosti',
    'LossOfIntegrity'=>'Ztráta Integrity',
    'LossOfAvailability'=>'Ztráta Dostupnosti',
    'LossOfAccountability'=>'Ztráta Odpovědnosti',
    'BusinessImpact'=>'Obchodní Dopad',
    'FinancialDamage'=>'Finanční Škody',
    'ReputationDamage'=>'Poškození Pověsti',
    'NonCompliance'=>'Porušení',
    'PrivacyViolation'=>'Porušení Ochrany Soukromí',
    'UpdateCustomScore'=>'Aktualizace Vlastní Skóre',
    'ManuallyEnteredValue'=>'Ručně Zadaná Hodnota',
    'ScoreByClassic'=>'Skóre Classic',
    'ScoreByCVSS'=>'Skóre CVSS',
    'ScoreByDREAD'=>'Skóre STRACH',
    'ScoreByOWASP'=>'Skóre podle OWASP',
    'ScoreByCustom'=>'Skóre tím, že Vlastní',
    'BaseVector'=>'Základní Vektor',
    'TemporalVector'=>'Časový Vektor',
    'EnvironmentalVector'=>'Životního Prostředí Vektor',
    'SupportingDocumentation'=>'Podklady',
    'Import'=>'Importovat',
    'Export'=>'Exportovat',
    'ActivateTheImportExportExtra'=>'Aktivovat Import/Export Extra',
    'PrintableView'=>'Tisk Zobrazit',
    'GroupBy'=>'Skupiny',
    'IncludedColumns'=>'Zahrnuty Sloupce',
    'AllRisks'=>'Všechna Rizika',
    'DynamicRiskReport'=>'Dynamické Rizikové Zprávy',
    'ObsoleteReports'=>'Zastaralé Zprávy',
    'Project'=>'Projekt',
    'SortBy'=>'Seřadit Podle',
    'MonthSubmitted'=>'Měsíc Předložen',
    'AssetManagement'=>'Správa aktiv',
    'AutomatedDiscovery'=>'Automatické Objev',
    'BatchImport'=>'Dávkový Import',
    'ManageAssets'=>'Správa Aktiv',
    'AssetValuation'=>'Oceňování Majetku',
    'AllowAccessToAssetManagementMenu'=>'Povolit Přístup k "Asset Management" Menu',
    'AutomatedDiscoveryHelp'=>'Objevte všechny živé IP adresy v zadaném rozsahu IP adres. Živé IP adresy budou přidány na seznam majetku. Přijatelné formáty:',
    'IPRange'=>'IP Rozsah',
    'Search'=>'Hledat',
    'AddANewAsset'=>'Přidat Nové Aktivum',
    'DeleteAnExistingAsset'=>'Odstranit Existující Aktiva',
    'AssetName'=>'Název Díla',
    'IPAddress'=>'IP Adresa',
    'AssetWasAddedSuccessfully'=>'Aktiv byl úspěšně přidán.',
    'AssetWasDeletedSuccessfully'=>'Aktiv byl úspěšně smazán.',
    'ThereWasAProblemAddingTheAsset'=>'Tam byl problém přidávání aktiv.',
    'ThereWasAProblemDeletingTheAsset'=>'Tam byl problém, odstraněním aktiva.',
    'ComingSoon'=>'Již Brzy',
    'ExportRisks'=>'Vývozní Rizika',
    'ExportMitigations'=>'Vývozní Omezení',
    'ExportReviews'=>'Export Recenze',
    'ExportCombined'=>'Export V Kombinaci',
    'MitigatedBy'=>'Zmírnit',
    'MitigationId'=>'Zmírnění ID',
    'ReviewId'=>'Recenze ID',
    'AffectedAssets'=>'Postižených Aktiv',
    'Activate'=>'Aktivovat',
    'DeleteRisks'=>'Odstranit Rizika',
    'DeletedRisksCannotBeRecovered'=>'Odstraněna Rizika Nemohou Být Vráceny',
    'RisksDeletedSuccessfully'=>'Riziko(s) Úspěšně Smazán',
    'ThereWasAProblemDeletingTheRisk'=>'Tam Byl Problém Odstranění Rizika(s)',
    'Activated'=>'AKTIVOVÁN',
    'IWantToReviewInsignificantRiskEvery'=>'Chci recenzi NEVÝZNAMNÉ riziko každý',
    'Insignificant'=>'Zanedbatelné',
    'IConsiderVeryHighRiskToBeAnythingGreaterThan'=>'Považuji za VELMI VYSOKÉ riziko být něco větší, než',
    'IConsiderHighRiskToBeLessThanAboveButGreaterThan'=>'Považuji za VYSOKÉ riziko je nižší než výše, ale větší než',
    'VeryHigh'=>'Velmi Vysoká',
    'VeryHighRisk'=>'Velmi Vysoké Riziko',
    'IWantToReviewVeryHighRiskEvery'=> 'Chci recenzi VELMI VYSOKÉ riziko každý',
    'AbleToReviewVeryHighRisks'=>'Možnost Přezkoumat Velmi Vysoká Rizika',
    'AbleToReviewInsignificantRisks'=>'Schopen Recenze Zanedbatelná Rizika',
    'TotalVeryHighRisks'=>'Celková Velmi Vysoká Rizika',
    'VeryHighRiskPercentage'=>'Velmi Vysoké Riziko, Procento',
    'AllTeams'=>'Všechny Týmy',
    'FileUploadSettings'=>'Nahrání Souboru Nastavení',
    'AllowedFileTypes'=>'Povolené Typy Souborů',
    'AddNewFileTypeOf'=>'Přidat nový typ souboru',
    'DeleteCurrentFileTypeOf'=>'Odstranit aktuální soubor typu',
    'MaximumUploadFileSize'=>'Maximální Nahrávání Velikost Souboru',
    'Bytes'=>'Bajtů',
    'CheckAll'=>'Zkontrolujte, Zda Všechny',
    'CheckAllRiskMgmt'=>'Zkontrolujte, Zda Všechny Řízení Rizik',
    'CheckAllAssetMgmt'=>'Zkontrolujte, Zda Všechny Asset Management',
    'CheckAllConfigure'=>'Zkontrolujte, Zda Všechny Konfigurace',
    'MitigationTeam'=>'Zmírnění Tým',
    'ImportRisks'=>'Import Rizika',
    'ImportAssets'=>'Import Majetku',
    'AssetValue'=>'Hodnota Aktiv',
    'Register'=>'Registrovat',
    'RegisterSimpleRisk'=>'Zaregistrujte SimpleRisk',
    'RegistrationText'=>'Registrací SimpleRisk budete poskytovat své kontaktní informace, takže si můžete být aktualizovány s nejnovější informace o verzi a důležitá bezpečnostní upozornění. Vaše informace nikdy nebudou prodány třetí straně. Registrované případy mají také schopnost být zálohovány a aktualizován s klepnutím na tlačítko.',
    'RegistrationInformation'=>'Registrační Informace',
    'Company'=>'Společnost',
    'JobTitle'=>'Název Práce',
    'Phone'=>'Telefon',
    'UpgradeSimpleRisk'=>'Inovace SimpleRisk',
    'UpgradeInstructions'=>'Tento oddíl používá Upgrade Navíc. Ujistěte se, že máte nejnovější verzi, vyberte možnost "Update", re-registrovat, a znovu načtěte tuto stránku.',
    'NoUpgradeNeeded'=>'Žádný upgrade je potřeba v této době.',
    'BackupDatabase'=>'Zálohování Databáze',
    'UpgradeApplication'=>'Upgrade Aplikace',
    'UpgradeDatabase'=>'Upgrade Databáze',
    'CustomExtras'=>'Vlastní Doplňky',
    'CustomExtrasText'=>'Bylo by úžasné, kdyby bylo všechno zadarmo, že? Doufejme, že jádro SimpleRisk platforma je schopna sloužit všechny vaše potřeby řízení rizik. Ale, pokud si najdete sami stále chtějí více funkcí, jsme vyvinuli sérii "Extra", že bude dělat jen to.',
    'Upgrade'=>'Inovace',
    'Install'=>'Instalace',
    'Purchase'=>'Nákup',
    'PasswordPolicy'=>'Heslo Politiky',
    'MinimumNumberOfCharacters'=>'Minimální Počet Znaků',
    'RequireAlphaCharacter'=>'Vyžadují Alfa Charakter',
    'RequireUpperCaseCharacter'=>'Vyžadují Velká Písmena, Znak',
    'RequireLowerCaseCharacter'=>'Vyžadují Nižší, Případ, Znak',
    'RequireNumericCharacter'=>'Vyžadují Číselných Znaků',
    'RequireSpecialCharacter'=>'Vyžadují Speciální Znak',
    'Enabled'=>'Povoleno',
    'RiskPyramid'=>'Riziko Pyramida',
    'RiskPyramidDescription'=>'Riziko pyramidy výše pomáhá ukázat rozdělení rizik mezi různé úrovně rizika. Top heavy pyramida může být znamení, že vaše organizace je, že na příliš velké riziko.',
    'RiskAdvice'=>'Riziko Radu',
    'AddDeleteAssets'=>'Přidat A Odstranit Aktiv',
    'EditAssets'=>'Edit Aktiv',
    'AutomaticAssetValuation'=>'Automatické Ocenění Aktiv',
    'ManualAssetValuation'=>'Manuální Oceňování Majetku',
    'MinimumValue'=>'Minimální Hodnota',
    'MaximumValue'=>'Maximální Hodnota',
    'ValueRange'=>'Rozsah Hodnot',
    'DefaultAssetValuation'=>'Výchozí Ocenění Aktiv',
    'Default'=>'Výchozí',
    'RisksAndAssets'=>'Rizika a Aktiva',
    'Report'=>'Zpráva',
    'RisksByAsset'=>'Rizik podle Aktiv',
    'AssetsByRisk'=>'Aktiv podle Rizika',
    'MaximumQuantitativeLoss'=>'Maximální Kvantitativní Ztráty',
    'MitigationOwner'=>'Zmírnění Majitel',
    'MitigationCost'=>'Ke Zmírnění Nákladů',
    'RiskColumns'=>'Riziko Sloupce',
    'MitigationColumns'=>'Zmírnění Sloupce',
    'ReviewColumns'=>'Recenze Sloupce',
    'ChangeStatus'=>'Změna Stavu',
    'SetRiskStatusTo'=>'Sada Riziko, Stav K',
    'AddNewStatusNamed'=>'Přidat nový status jménem',
    'DeleteStatusNamed'=>'Odstranit status jménem',
    'DefaultCurrencySymbol'=>'Výchozí Symbol Měny',
    'DefaultValues'=>'Výchozí Hodnoty',
    'RiskSource'=>'Riziko Zdroj',
    'AddNewSourceNamed'=>'Přidat nový zdroj jménem',
    'DeleteSourceNamed'=>'Odstranit zdroj jménem',
    'CheckAllAssessments'=>'Zkontrolujte, Zda Všechny Hodnocení',
    'AllowAccessToAssessmentsMenu'=>'Povolit Přístup k "Posouzení" Menu',
    'Assessments'=>'Hodnocení',
    'AvailableAssessments'=>'K Dispozici Hodnocení',
    'PendingRisks'=>'Čeká Rizika',
    'CreateAssessment'=>'Vytvořit Hodnocení',
    'EditAssessment'=>'Upravit Hodnocení',
    'Overview'=>'Přehled',
    'OpenVsClosed'=>'Otevřený vs Uzavřený',
    'MitigatedVsUnmitigated'=>'Zmírnit vs Naprostá',
    'ReviewedVsUnreviewed'=>'Přezkoumány vs Nezařazených',
    'OpenedRisks'=>'Otevřel Rizika',
    'MailSettings'=>'Nastavení Pošty',
    'TransportAgent'=>'Agent přenosu',
    'FromName'=>'Z Název',
    'FromEmail'=>'Z E-Mailu',
    'ReplyToName'=>'Odpověď Na Jméno',
    'ReplyToEmail'=>'Odpověď Na E-Mail',
    'Host'=>'Hostitel',
    'SMTPAuthentication'=>'SMTP Autentizace',
    'Encryption'=>'Šifrování',
    'Port'=>'Přístav',
    'Next'=>'Další',
    'NewAssessmentQuestion'=>'Nové Posouzení Otázku',
    'Question'=>'Otázka',
    'RiskScore'=>'Skóre Rizika',
    'SubmitRisk'=>'Předložit Riziko',
    'Answer'=>'Odpověď',
    'AddQuestion'=>'Přidat Otázku',
    'SaveAssessment'=>'Uložit Hodnocení',
    'SendAssessment'=>'Poslat Hodnocení',
    'DeleteAssessment'=>'Odstranit Hodnocení',
    'AssessmentName'=>'Hodnocení Jméno',
    'SendTo'=>'Poslat',
    'ActiveAssessments'=>'Aktivní Hodnocení',
    'SentTo'=>'Poslal',
    'From'=>'Z',
    'Key'=>'Klíč',
    'GoToSSOLoginPage'=>'Přejděte na stránku přihlášení SSO',
    'APIKey'=>'Klíč API',
    'GenerateAPIKey'=>'Generovat klíč API',
    'RotateAPIKey'=>'Otočit klíč API',
    'InvalidateAPIKey'=>'Zneplatnit klíč API',
    'Deactivate'=>'Deaktivovat',
    'ImportExportExtra'=>'Import-Export Extra',
    'SaveDetails'=>'Uložení podrobností',
    'ClearForm'=>'Vymazat formulář',
    'SaveMitigation'=>'Uložit ke zmírnění',
    'Cancel'=>'Zrušit',
    'SubmitReview'=>'Odeslat recenzi',
    'UnassignedRisks'=>'Nepřiřazené rizika',
    'DisableRegistrationNotice'=>'Zakázat registrace oznámení',
    'UserPolicy'=>'Zásady uživatele',
    'UseCaseSensitiveValidationOfUsername'=>'Použít velká a malá písmena ověření uživatelské jméno',
    'MitigationPlanning'=>'Datum plánované snižování',
    'AssetDetails'=>'Podrobnosti o majetku',
    'RiskList'=>'Seznam rizik',
    'Are you sure you want to close the risk? All changes will be lost!'=>'Jste si jistý, že chcete zavřít riziko? Všechny změny budou ztraceny!',
    'MinimumPasswordAge' => 'Minimální stáří hesla',
    'MaximumPasswordAge' => 'Maximální stáří hesla',
    'MaximumAttemptsLockout' => 'Maximální počet pokusů uzamčení',
    'MaximumAttemptsLockoutTime' => 'Doba uzamčení maximální počet pokusů',
    'attempts' => 'pokusy',
    'minutes' => 'minut',
    'AccountLockedOut' => 'Účet je uzamčen',
    'AccountLockoutPolicy' => 'Zásady uzamčení účtů',
    'ImportExportIsDeactivated' => 'Dovoz vývoz je deaktivován',
    'PurchaseTheExtra' => 'Nákup Extra',
    'ExpandAll' => 'Rozbalit vše',
    'ConditionMessageForMinChar' => 'Heslo by mělo obsahovat nejméně $min_chars znaků.',
    'ConditionMessageForAlpha' => 'Heslo by mělo obsahovat alfanumerický znak.',
    'ConditionMessageForUppercase' => 'Heslo by mělo obsahovat znak velká písmena.',
    'ConditionMessageForLowercase' => 'Heslo by mělo obsahovat malé písmeno.',
    'ConditionMessageForDigit' => 'Heslo musí obsahovat číslice.',
    'ConditionMessageForSpecialchar' => 'Heslo by mělo obsahovat speciální znaky.',
    'ConditionMessageForMinPasswordAge' => 'Heslo může být aktualizován z poslední aktualizace času minimální $min_password_age dní později.',
    'TrustedDomains' => 'Důvěryhodné domény',
    'SimpleRiskColumnMapping' => 'Mapování sloupce SimpleRisk',
    'Mapping' => 'Mapování',
    'Optional' => 'Nepovinné',
    'SaveMappingAs' => 'Uložit mapování jako',
    'EncryptionLevel' => 'Úroveň šifrování',
    'Level' => 'Úroveň',
    'Description' => 'Popis',
    'ShowRiskScoreOverTime' => 'Zobrazit skóre rizika v průběhu času',
    'HideRiskScoreOverTime' => 'Skrýt skóre rizika v průběhu času',
    'ReviewRegularlyHelp' => 'Níže je seznam všech rizik, které jsou řazeny podle Unreviewed, minulých splatnosti a datum příštího přezkumu',
    'RiskScoringHistory' => 'Riziko bodování historie',
    'RiskAddPermissionMessage' => 'Nemáte oprávnění k odeslání nová rizika.  Všechna rizika, pokoušejících se o odeslání nebudou zaznamenány.  Pokud máte pocit, že dosáhli jste tuto zprávu obdrželi omylem, obraťte se na správce.',
    'SubjectRiskCannotBeEmpty' => 'Předmět riziko nemůže být prázdná',
    'InvalidRiskID' => 'Neplatné ID rizika',
    'Success' => 'Úspěch',
    'RiskUpdatePermissionMessage' => 'Nemáte oprávnění ke změně rizika.  Všechna rizika, pokoušejících se měnit nebudou zaznamenány.  Pokud máte pocit, že dosáhli jste tuto zprávu obdrželi omylem, obraťte se na správce.',
    'RiskReviewPermission' => 'Nemáte oprávnění ke kontrole rizika na úrovni $risk_level.  Hodnocení, pokoušejících se o odeslání nebudou zaznamenány.  Pokud máte pocit, že dosáhli jste tuto zprávu obdrželi omylem, obraťte se na správce.',
    'DateAndTime' => 'Datum a čas',
    'mCryptWarning' => '"Mcrypt" rozšíření musí být nainstalována pro šifrování navíc pracovat správně.  Prosím, nainstalujte ji nadále.',
    'APIInCompatibleWithEncryptionLevel' => 'Rozhraní API není kompatibilní s úrovní šifrování uživatelů šifrované databáze navíc.',
    'UnauthenticatedAccessInAPI' => 'Neověřený přístup.  Přihlaste se nebo poskytnout klíč k použití SimpleRisk API.',
    'ResetPasswordMessageInUserLevelEncryption' => 'Hesla nelze obnovit, protože je tento uživatel v uživatelské úrovni šifrování. Obraťte se na správce.',
    'YouNeedToSpecifyAnIdParameter' => 'Je třeba zadat id parametr.',
    'NoMitigation' => 'Neexistuje žádný určený ke zmírnění.',
    'NoReview' => 'Neexistuje žádný zadaný recenzi.',
    'MysqldumpPathWasSavedSuccessfully'=>'Mysqldump cesta byl úspěšně uložen.',
    'UnavailableMysqldumpService'=>'Na serveru není žádná "mysqldump" služba k dispozici. Prosím nastavte cestu absolutní mysqldump služby.',
    'AllOpenRisksByTeamByLevel'=>'Všechny otevřené rizika týmem podle úrovně rizika',
    '' => '',
);

?>
