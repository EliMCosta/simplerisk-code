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
    'Home'=>'Haus',
    'RiskManagement'=>'Risiko-Management',
    'Reporting'=>'Berichterstattung',
    'Configure'=>'Konfigurieren',
    'MyProfile'=>'Mein Profil',
    'Logout'=>'Logout',
    'LogInHere'=>'Login zu SimpleRisk',
    'Username'=>'Benutzername',
    'Password'=>'Passwort',
    'ForgotYourPassword'=>'Haben Sie Ihr Passwort vergessen',
    'Login'=>'Login',
    'Reset'=>'Zurücksetzen',
    'Send'=>'Senden',
    'Update'=>'Update',
    'SendPasswordResetEmail'=>'Senden Sie Passwort-Reset-E-Mail',
    'PasswordReset'=>'Passwort Zurücksetzen',
    'ResetToken'=>'Reset-Token',
    'RepeatPassword'=>'Wiederholen Sie Das Passwort',
    'Submit'=>'Senden',
    'ProfileDetails'=>'Profil Details',
    'LastLogin'=>'Letzter Login',
    'ChangePassword'=>'Passwort Ändern',
    'CurrentPassword'=>'Aktuelles Passwort',
    'NewPassword'=>'Neues Passwort',
    'ConfirmPassword'=>'Kennwort Bestätigen',
    'ConfigureRiskFormula'=>'Konfigurieren Risiko Formel',
    'ConfigureReviewSettings'=>'Konfigurieren Sie Einstellungen Überprüfen',
    'AddAndRemoveValues'=>'Hinzufügen und Entfernen von Werten',
    'UserManagement'=>'Benutzerverwaltung',
    'RedefineNamingConventions'=>'Definieren Von Namenskonventionen',
    'AuditTrail'=>'Audit-Trail',
    'Extras'=>'Extras',
    'Announcements'=>'Ankündigungen',
    'About'=>'Über',
    'Impact'=>'Auswirkungen',
    'Likelihood'=>'Wahrscheinlichkeit',
    'MitigationEffort'=>'Abmilderungsbemühungen',
    'Change'=>'Ändern',
    'to'=>'zu',
    'AddANewUser'=>'Einen Neuen Benutzer hinzufügen',
    'Type'=>'Geben',
    'FullName'=>'Vollständiger Name',
    'EmailAddress'=>'E-mail-Adresse',
    'Teams'=>'Gruppe(n)',
    'ALL'=>'ALLE',
    'NONE'=>'KEINER',
    'UserResponsibilities'=>'Pflichten Des Nutzers',
    'AbleToSubmitNewRisks'=>'Einreichen können, Neue Risiken',
    'AbleToModifyExistingRisks'=>'In der Lage zu Ändern Vorhandener Risiken',
    'AbleToCloseRisks'=>'In der Lage zu Schließen, Risiken',
    'AbleToPlanMitigations'=>'In der Lage zu Planen, Schutzmaßnahmen',
    'AbleToReviewLowRisks'=>'In der Lage zu Überprüfen, Geringe Risiken',
    'AbleToReviewMediumRisks'=>'In der Lage zu Überprüfen, Mittlere Risiken',
    'AbleToReviewHighRisks'=>'In der Lage zu Überprüfen, Hohe Risiken',
    'AllowAccessToConfigureMenu'=>'Zugriff gestatten auf "Konfigurieren" - Menü',
    'MultiFactorAuthentication'=>'Multi-Faktor-Authentifizierung',
    'None'=>'Keiner',
    'Add'=>'Hinzufügen',
    'ViewDetailsForUser'=>'Anzeigen von Details für die User',
    'DetailsForUser'=>'Details für User',
    'Select'=>'Wählen Sie',
    'EnableAndDisableUsers'=>'Aktivieren und Deaktivieren von Benutzern',
    'EnableAndDisableUsersHelp'=>'Verwenden Sie diese Funktion aktivieren oder deaktivieren, Benutzer-logins, die bei Beibehaltung des audit-trail der Aktivitäten der Benutzer',
    'DisableUser'=>'Benutzer deaktivieren',
    'Disable'=>'Deaktivieren',
    'EnableUser'=>'Dem Benutzer ermöglichen,',
    'Enable'=>'Aktivieren',
    'DeleteAnExistingUser'=>'Löschen eines Vorhandenen Benutzers',
    'DeleteCurrentUser'=>'Löschen Sie den aktuellen Benutzer',
    'Delete'=>'Löschen',
    'SendPasswordResetEmailForUser'=>'Senden Sie Passwort-reset-E-Mails für Benutzer',
    'Category'=>'Kategorie',
    'AddNewCategoryNamed'=>'Fügen Sie die neue Kategorie mit dem Namen',
    'DeleteCurrentCategoryNamed'=>'Löschen der aktuellen Kategorie mit dem Namen',
    'Team'=>'Team',
    'AddNewTeamNamed'=>'Hinzufügen von neuen team-namens',
    'DeleteCurrentTeamNamed'=>'Löschen Sie die aktuelle team-namens',
    'Technology'=>'Technologie',
    'AddNewTechnologyNamed'=>'Fügen Sie neue Technologie mit dem Namen',
    'DeleteCurrentTechnologyNamed'=>'Löschen Sie die aktuelle Technologie, mit dem Namen',
    'SiteLocation'=>'Ort/Lage',
    'AddNewSiteLocationNamed'=>'Hinzufügen neuer Standort/location benannt',
    'DeleteCurrentSiteLocationNamed'=>'Löschen Sie die aktuelle Lage/Standort benannt',
    'ControlRegulation'=>'Verordnung Über Die Kontrolle',
    'AddNewControlRegulationNamed'=>'Fügen Sie neue Verordnung über die Kontrolle benannt',
    'DeleteCurrentControlRegulationNamed'=>'Löschen Sie die aktuelle Verordnung über die Kontrolle benannt',
    'RiskPlanningStrategy'=>'Risiko-Planung-Strategie',
    'AddNewRiskPlanningStrategyNamed'=>'Fügen Sie neue Risiko-Planung-Strategie benannt',
    'DeleteCurrentRiskPlanningStrategyNamed'=>'Löschen Sie die aktuelle Risiko-Planung-Strategie benannt',
    'CloseReason'=>'Engen Grund',
    'AddNewCloseReasonNamed'=>'Hinzufügen von neuen engen Grund genannt',
    'DeleteCurrentCloseReasonNamed'=>'Löschen der aktuellen engen Grund genannt',
    'IWantToReviewHighRiskEvery'=>'Ich möchte überprüfen, HOHEM Risiko jede',
    'IWantToReviewMediumRiskEvery'=>'Ich möchte überprüfen, MITTLERES Risiko jede',
    'IWantToReviewLowRiskEvery'=>'Ich möchte abgeben GERINGE Gefahr jede',
    'days'=>'Tage',
    'MyClassicRiskFormulaIs'=>'Meine Klassische Risiko-Formel Ist',
    'RISK'=>'RISIKO',
    'IConsiderHighRiskToBeAnythingGreaterThan'=>'Ich halte HOHES Risiko zu sein, die etwas größer als',
    'IConsiderMediumRiskToBeLessThanAboveButGreaterThan'=>'Ich halte ein MITTLERES Risiko auf weniger als oben, aber größer als',
    'IConsiderlowRiskToBeLessThanAboveButGreaterThan'=>'Ich halte mit GERINGEM Risiko weniger als oben, aber größer als',
    'HighRisk'=>'Hohes Risiko',
    'MediumRisk'=>'Mittleres Risiko',
    'LowRisk'=>'Geringes Risiko',
    'Irrelevant'=>'Irrelevant',
    'SubmitYourRisks'=>'Senden Sie Risiko',
    'PlanYourMitigations'=>'Plan-Minderung',
    'PerformManagementReviews'=>'Bewertungen durchführen',
    'PrioritizeForProjectPlanning'=>'Projekte planen',
    'ReviewRisksRegularly'=>'Überprüfen Sie regelmäßig',
    'DocumentANewRisk'=>'Dokument ein Neues Risiko',
    'UseThisFormHelp'=>'Verwenden Sie dieses Formular, um zu dokumentieren, eine neue Gefahr für die Berücksichtigung im Risiko-Management-Prozess',
    'Subject'=>'Thema',
    'ExternalReferenceId'=>'Externe Referenz-ID',
    'ControlNumber'=>'Steuer-Nummer',
    'Owner'=>'Eigentümer',
    'OwnersManager'=>'Eigentümer Manager',
    'RiskScoringMethod'=>'Risiko-Scoring-Methode',
    'CurrentLikelihood'=>'Die Aktuelle Wahrscheinlichkeit, Dass',
    'CurrentImpact'=>'Aktuelle Auswirkungen',
    'RiskAssessment'=>'Risikobewertung',
    'AdditionalNotes'=>'Zusätzliche Hinweise',
    'UNREVIEWED'=>'UNGEPRÜFT,',
    'PASTDUE'=>'ÜBERFÄLLIG',
    'ID'=>'ID',
    'Status'=>'Status',
    'Risk'=>'Risiko',
    'DaysOpen'=>'Tage Geöffnet',
    'CalculatedRisk'=>'Kalkuliertes Risiko',
    'SubmittedBy'=>'Verfasst Von',
    'NextReviewDate'=>'Nächste Überprüfung Datum',
    'CVSSRiskScoring'=>'CVSS Risiko-Scoring',
    'DREADRiskScoring'=>'Angst, Risiko Bewertung',
    'OWASPRiskScoring'=>'OWASP Risiko Bewertung',
    'CustomRiskScoring'=>'Kundenspezifische Risiko-Scoring',
    'MitigationPlanningHelp'=>'Unten ist die Liste der eingegangenen Risiken benötigen mitigation Planung',
    'ManagementReviewHelp'=>'Unten ist die Liste der eingegangenen Risiken, erfordern ein management-review',
    'Submitted'=>'Eingereicht',
    'MitigationPlanned'=>'Klimaschutz Geplant',
    'ManagementReview'=>'Management-Review',
    'No'=>'Keine',
    'Yes'=>'Ja',
    'AddAndRemoveProjects'=>'Hinzufügen und Entfernen von Projekten',
    'AddAndRemoveProjectsHelp'=>'Hinzufügen und entfernen von Projekten in Auftrag zu verknüpfen, die mehrere Risiken zusammen, die für die Priorisierung',
    'AddNewProjectNamed'=>'Fügen Sie neue Projekt mit dem Namen',
    'DeleteCurrentProjectNamed'=>'Löschen Sie die aktuelle Projekt mit dem Namen',
    'AddUnassignedRisksToProjects'=>'Fügen Sie die nicht zugewiesenen Risiken für Projekte',
    'AddUnassignedRisksToProjectsHelp'=>'Drag-and-drop zugewiesen Risiken gekennzeichnet sind, für die Betrachtung als Projekt in das entsprechende Register "Projekt"',
    'PrioritizeProjects'=>'Priorisieren Projekte',
    'PrioritizeProjectsHelp'=>'Verschieben von Projekten um und ändern Sie die Reihenfolge der Priorisierung. Sobald Sie fertig sind, vergessen Sie nicht, drücken die Schaltfläche "Aktualisieren" um die änderungen zu speichern',
    'SaveRisksToProjects'=>'Speichern Sie die Risiken für Projekte',
    'RiskId'=>'Risiko-ID',
    'RiskActions'=>'Aktionen',
    'ReopenRisk'=>'Öffnen Risiko',
    'CloseRisk'=>'Schließen Risiko',
    'EditRisk'=>'Edit Risiko',
    'PlanAMitigation'=>'Planen Sie eine Abschwächung',
    'PerformAReview'=>'Führen Sie eine Überprüfung',
    'AddAComment'=>'Fügen Sie einen Kommentar hinzu',
    'ShowRiskScoringDetails'=>'Ansicht-Risiko Scoring-Details',
    'HideRiskScoringDetails'=>'Verbergen Risiko-Scoring Details',
    'Details'=>'Details',
    'SubmissionDate'=>'Das Datum Der Einreichung',
    'DateSubmitted'=>'Datum Eingereicht',
    'EditDetails'=>'Details Bearbeiten',
    'Mitigation'=>'Klimaschutz',
    'MitigationDate'=>'Schadensminderung Abgabetermin',
    'PlanningStrategy'=>'Planung Strategie',
    'CurrentSolution'=>'Aktuelle Lösung',
    'SecurityRequirements'=>'Security-Anforderungen',
    'SecurityRecommendations'=>'Empfehlungen Für Die Sicherheit',
    'EditMitigation'=>'Edit Klimaschutz',
    'LastReview'=>'Letzte Bewertung',
    'ReviewDate'=>'Review Datum',
    'Reviewer'=>'Rezensent',
    'Review'=>'Überprüfen',
    'NextStep'=>'Im Nächsten Schritt',
    'Comments'=>'Kommentare',
    'ViewAllReviews'=>'Alle Bewertungen Anzeigen',
    'ReviewHistory'=>'Überprüfung Der Geschichte',
    'Comment'=>'Kommentar',
    'ClassicRiskScoring'=>'Klassische Risiko-Scoring',
    'RiskScoringActions'=>'Risiko-Scoring-Aktionen',
    'UpdateClassicScore'=>'Klassische Score zu aktualisieren',
    'ScoreBy'=>'Partitur von',
    'RISKClassicExp1'=>'RISIKO = ( Wahrscheinlichkeit x Auswirkung + 2(Impact -) )',
    'RISKClassicExp2'=>'RISIKO = ( Wahrscheinlichkeit x Auswirkung + Auswirkung )',
    'RISKClassicExp3'=>'RISIKO = ( Wahrscheinlichkeit x Auswirkung )',
    'RISKClassicExp4'=>'RISIKO = ( Wahrscheinlichkeit x Auswirkung + Wahrscheinlichkeit )',
    'RISKClassicExp5'=>'RISIKO = ( Wahrscheinlichkeit x Auswirkung + 2(Wahrscheinlichkeit) )',
    'Reason'=>'Grund',
    'CloseOutInformation'=>'Close-out-Informationen',
    'SubmitManagementReview'=>'Senden Management-Review',
    'SubmitRiskMitigation'=>'Senden Risikominimierung',
    'RiskDashboard'=>'Risiko-Dashboard',
    'RiskTrend'=>'Risiko Trend',
    'AllOpenRisksAssignedToMeByRiskLevel'=>'Alle offenen Risiken, die mir zugewiesene',
    'AllOpenRisksByRiskLevel'=>'Alle Offenen Risiken durch Risiko-Level',
    'AllOpenRisksConsideredForProjectsByRiskLevel'=>'Alle Offenen Risiken Als für Projekte, die vom Risiko-Level',
    'AllOpenRisksAcceptedUntilNextReviewByRiskLevel'=>'Alle Offenen Risiken Akzeptiert, Bis zum Nächsten Review von Risiko-Level',
    'AllOpenRisksToSubmitAsAProductionIssueByRiskLevel'=>'Alle Offenen Risiken für das Senden als ein Problem der Erzeugung von Risiko-Level',
    'AllOpenRisksByScoringMethod'=>'Alle Offenen Risiken von Scoring-Methode',
    'AllClosedRisksByRiskLevel'=>'Alle Geschlossenen Risiken durch Risiko-Level',
    'SubmittedRisksByDate'=>'Eingereicht Risiken Nach Datum',
    'MitigationsByDate'=>'Schutzmaßnahmen Nach Datum',
    'ManagementReviewsByDate'=>'Management Reviews Nach Datum',
    'ProjectsAndRisksAssigned'=>'Projekte und Risiken Zugeordnet',
    'OpenRisks'=>'Offene Risiken',
    'ClosedRisks'=>'Geschlossen Risiken',
    'ReportMyOpenHelp'=>'Dieser Bericht zeigt alle offenen Risiken, die der aktuelle Benutzer als Besitzer oder manager im Zusammenhang mit dem Risiko bestellt durch Risiko',
    'ReportOpenHelp'=>'Dieser Bericht zeigt alle offenen Risiken bestellt ist Risiko',
    'ReportProjectsHelp'=>'Dieser Bericht zeigt alle offenen Risiken als für Projekte bestellt durch Risiko',
    'ReportNextReviewHelp'=>'Dieser Bericht zeigt alle offenen Risiken akzeptiert, bis die nächste überprüfung bestellt durch Risiko',
    'ReportProductionIssuesHelp'=>'Dieser Bericht zeigt alle offenen Risiken eingereicht als Produktions-Probleme bestellt durch Risiko',
    'ReportRiskScoringHelp'=>'Dieser Bericht zeigt alle Risiko-scoring-Verfahren und Methoden die Risiken bewertet jeder',
    'ReportClosedHelp'=>'Dieser Bericht zeigt alle geschlossenen Risiken bestellt ist Risiko',
    'ReportSubmittedByDateHelp'=>'Dieser Bericht zeigt, dass alle Risiken, bestellt durch das Datum der Einreichung',
    'ReportMitigationsByDateHelp'=>'Dieser Bericht zeigt alle schadensbegrenzenden Maßnahmen geplant, bestellt durch Minderung Datum',
    'ReportMgmtReviewsByDateHelp'=>'Dieser Bericht zeigt alle management-reviews bestellt bei der überprüfung Datum',
    'ReportProjectsAndRisksHelp'=>'Dieser Bericht zeigt alle Projekte und die Risiken der Ihnen zugewiesenen',
    'Language'=>'Sprache',
    'AllOpenRisksNeedingReview'=>'Alle Offenen Risiken Benötigen eine Überprüfung',
    'ReportReviewNeededHelp'=>'Dieser Bericht zeigt alle offenen Risiken benötigen ein management-review',
    'CustomValue'=>'Benutzerdefinierte Wert',
    'ClosedRisksByDate'=>'Geschlossen, Risiken Von Datum',
    'DateClosed'=>'Datum Geschlossen',
    'ClosedBy'=>'Geschlossen',
    'ReportClosedByDateHelp'=>'Dieser Bericht zeigt, dass alle Risiken, bestellt von der Schließung Datum',
    'AllOpenRisksByTeam'=>'Alle Offenen Risiken-Team',
    'ReportRiskTeamsHelp'=>'Dieser Bericht zeigt alle teams und die Risiken, die jeweils zugeordneten',
    'Unassigned'=>'Nicht zugewiesen',
    'AllOpenRisksByTechnology'=>'Alle Offenen Risiken Durch Technologie',
    'ReportRiskTechnologiesHelp'=>'Dieser Bericht zeigt alle Technologien und die Risiken jeweils zugeordneten',
    'RiskLevel'=>'Risiko',
    'BasedOnTheCurrentRiskScore'=>'Basierend auf Ihrem Risiko-Score, werden Ihre nächsten Prüfungsdatum ',
    'WouldYouLikeToUseADifferentDate'=>'Möchten Sie Sie mit einem anderen Datum statt?',
    'RisksOpenedAndClosedOverTime'=>'Risiken, die Geöffnet und Geschlossen im Laufe der Zeit',
    'AllRiskScoresAreAdjusted'=>'Alle Risikobewertungen angepasst auf einer 0-10 Skala.',
    'DetermineProjectStatus'=>'Bestimmen Projekt-Status',
    'ProjectStatusHelp'=>'Ort Projekte in Gruppen basierend auf Ihren aktuellen status.',
    'ActiveProjects'=>'Aktive Projekte',
    'OnHoldProjects'=>'On-Hold-Projekte',
    'CompletedProjects'=>'Abgeschlossene Projekte',
    'CancelledProjects'=>'Stornierte Projekte',
    'UpdateProjectStatuses'=>'Update-Projekt-Status',
    'HighRiskReport'=>'High-Risk-Bericht',
    'TotalOpenRisks'=>'Insgesamt Öffnen Risiken',
    'TotalHighRisks'=>'Insgesamt Hohe Risiken',
    'HighRiskPercentage'=>'Hohes Risiko Prozentsatz',
    'UpdateClassicScore'=>'Klassische Score zu aktualisieren',
    'CurrentLikelihood'=>'Aktuelle Wahrscheinlichkeit',
    'CurrentImpact'=>'Aktuelle Auswirkungen',   
    'UpdateCVSSScore'=>'Update CVSS-Score',
    'BaseScoreMetrics'=>'Basis-Score-Metrics',
    'AttackVector'=>'Angriffsmethode',
    'AttackComplexity'=>'Angriff Komplexität',
    'Authentication'=>'Authentifizierung',
    'ConfidentialityImpact'=>'Vertraulichkeit Auswirkungen',
    'IntegrityImpact'=>'Integrität Auswirken',
    'AvailabilityImpact'=>'Verfügbarkeit',
    'TemporalScoreMetrics'=>'Zeitliche Partitur Metriken',
    'Exploitability'=>'Ausnutzbar',
    'RemediationLevel'=>'Sanierung-Ebene',
    'ReportConfidence'=>'Bericht Vertrauen',
    'EnvironmentalScoreMetrics'=>'Umwelt-Score-Metrics',
    'CollateralDamagePotential'=>'Möglichen Kollateralschäden',
    'TargetDistribution'=>'Ziel-Verteilung',
    'ConfidentialityRequirement'=>'Geheimhaltungsverpflichtung',
    'IntegrityRequirement'=>'Integrität-Anforderung',
    'AvailabilityRequirement'=>'Verfügbarkeit Voraussetzung',
    'UpdateDREADScore'=>'Update DREAD Punktzahl',
    'DamagePotential'=>'Schaden',
    'Reproducibility'=>'Reproduzierbarkeit',
    'AffectedUsers'=>'Die Betroffenen Nutzer',
    'Discoverability'=>'Auffindbarkeit',
    'UpdateOWASPScore'=>'Update OWASP Punktzahl',
    'ThreatAgentFactors'=>'Angriffspunkte Faktoren',
    'SkillLevel'=>'Skill-Level',
    'Motive'=>'Motiv',
    'Opportunity'=>'Gelegenheit',
    'Size'=>'Größe',
    'VulnerabilityFactors'=>'Sicherheitsanfälligkeit Faktoren',
    'EaseOfDiscovery'=>'Einfache Entdeckung',
    'EaseOfExploit'=>'Leichtigkeit Auszunutzen',
    'Awareness'=>'Bewusstsein',
    'IntrusionDetection'=>'Intrusion Detection',
    'TechnicalImpact'=>'Technische Auswirkungen',
    'LossOfConfidentiality'=>'Verlust der Vertraulichkeit',
    'LossOfIntegrity'=>'Verlust der Integrität',
    'LossOfAvailability'=>'Verlust der Verfügbarkeit',
    'LossOfAccountability'=>'Verlust der Zurechenbarkeit',
    'BusinessImpact'=>'Auswirkungen auf das Geschäft',
    'FinancialDamage'=>'Finanzielle Schäden',
    'ReputationDamage'=>'Ruf Schaden',
    'NonCompliance'=>'Non-Compliance',
    'PrivacyViolation'=>'Verstoß Gegen Den Datenschutz',
    'UpdateCustomScore'=>'Update Benutzerdefinierte Partitur',
    'ManuallyEnteredValue'=>'Manuell Eingegebene Wert',
    'ScoreByClassic'=>'Partitur von Klassischen',
    'ScoreByCVSS'=>'Partitur von CVSS',
    'ScoreByDREAD'=>'Partitur von DREAD',
    'ScoreByOWASP'=>'Partitur von OWASP',
    'ScoreByCustom'=>'Partitur von Benutzerdefinierten',
    'BaseVector'=>'Basis-Vektor',
    'TemporalVector'=>'Zeitliche Vektor',
    'EnvironmentalVector'=>'Umwelt-Vektor',
    'SupportingDocumentation'=>'Unterlagen',
    'Import'=>'Import',
    'Export'=>'Export',
    'ActivateTheImportExportExtra'=>'Aktivieren Sie die Import - /Export-Extra',
    'PrintableView'=>'Druckbare Ansicht',
    'GroupBy'=>'Gruppe Von',
    'IncludedColumns'=>'Eingeschlossene Spalten Enthalten',
    'AllRisks'=>'Alle Risiken',
    'DynamicRiskReport'=>'Dynamische Risiko-Bericht',
    'ObsoleteReports'=>'Veraltete Berichte',
    'Project'=>'Projekt',
    'SortBy'=>'Sortieren Nach',
    'MonthSubmitted'=>'Monat Eingereicht',
    'AssetManagement'=>'Asset Management',
    'AutomatedDiscovery'=>'Die Automatisierte Erkennung',
    'BatchImport'=>'Batch-Import',
    'ManageAssets'=>'Verwalten Von Assets',
    'AssetValuation'=>'Vermögensbewertung',
    'AllowAccessToAssetManagementMenu'=>'Ermöglichen den Zugang zu "Asset Management" - Menü',
    'AutomatedDiscoveryHelp'=>'Entdecken Sie alle live-IP-Adressen in den IP-Bereich eingegeben. Live-IP-Adressen werden Hinzugefügt, um die asset-Liste. Akzeptiert werden folgende Formate:',
    'IPRange'=>'IP-Bereich',
    'Search'=>'Suche',
    'AddANewAsset'=>'Hinzufügen eines Neuen Asset',
    'DeleteAnExistingAsset'=>'Löschen Sie ein Vorhandenes Asset',
    'AssetName'=>'Asset-Namen',
    'IPAddress'=>'Die IP-Adresse',
    'AssetWasAddedSuccessfully'=>'Das Asset wurde erfolgreich Hinzugefügt.',
    'AssetWasDeletedSuccessfully'=>'Das Asset wurde erfolgreich gelöscht.',
    'ThereWasAProblemAddingTheAsset'=>'Es wurde ein problem bei dem hinzufügen der Assets.',
    'ThereWasAProblemDeletingTheAsset'=>'Es gab ein problem beim löschen des Vermögenswertes.',
    'ComingSoon'=>'Demnächst',
    'ExportRisks'=>'Export-Risiken',
    'ExportMitigations'=>'Export Schutzmaßnahmen',
    'ExportReviews'=>'Exportieren Bewertungen',
    'ExportCombined'=>'Export-Kombination',
    'MitigatedBy'=>'Gemildert',
    'MitigationId'=>'Klimaschutz-ID',
    'ReviewId'=>'Überprüfen Sie ID',
    'AffectedAssets'=>'Die Betroffenen Vermögenswerte',
    'Activate'=>'Aktivieren',
    'DeleteRisks'=>'Löschen Von Risiken',
    'DeletedRisksCannotBeRecovered'=>'Gelöscht Risiken Können Nicht Wiederhergestellt Werden',
    'RisksDeletedSuccessfully'=>'Risk(s) Erfolgreich Gelöscht',
    'ThereWasAProblemDeletingTheRisk'=>'Es Gab ein Problem beim Löschen die Gefahr(en)',
    'Activated'=>'AKTIVIERT',
    'IWantToReviewInsignificantRiskEvery'=>'Ich möchte überprüfen unerhebliches Risiko, jeder',
    'Insignificant'=>'Unbedeutend',
    'IConsiderVeryHighRiskToBeAnythingGreaterThan'=>'Ich halte das Risiko SEHR HOCH zu sein, die etwas größer als',
    'IConsiderHighRiskToBeLessThanAboveButGreaterThan'=>'Ich halte HOHES Risiko auf weniger als oben, aber größer als',
    'VeryHigh'=>'Sehr Hohe',
    'VeryHighRisk'=>'Sehr Hohes Risiko',
    'IWantToReviewVeryHighRiskEvery'=> 'Ich möchte die review SEHR HOHEM Risiko jede',
    'AbleToReviewVeryHighRisks'=>'In der Lage zu Überprüfen, Sehr Hohe Risiken',
    'AbleToReviewInsignificantRisks'=>'In der Lage zu Überprüfen, Unbedeutende Risiken',
    'TotalVeryHighRisks'=>'Insgesamt Sehr Hohe Risiken',
    'VeryHighRiskPercentage'=>'Sehr Hohes Risiko Prozentsatz',
    'AllTeams'=>'Alle Teams',
    'FileUploadSettings'=>'Datei-Upload-Einstellungen',
    'AllowedFileTypes'=>'Erlaubte Datei-Typen',
    'AddNewFileTypeOf'=>'Hinzufügen eines neuen Datei-Typ',
    'DeleteCurrentFileTypeOf'=>'Löschen Sie die aktuelle Datei-Typ',
    'MaximumUploadFileSize'=>'Maximale Upload-Dateigröße',
    'Bytes'=>'Byte',
    'CheckAll'=>'Überprüfen Sie Alle',
    'CheckAllRiskMgmt'=>'Überprüfen Sie Alle Risiko-Management',
    'CheckAllAssetMgmt'=>'Überprüfen Sie Alle Asset Management',
    'CheckAllConfigure'=>'Überprüfen Sie Alle Konfigurieren',
    'MitigationTeam'=>'Klimaschutz-Team',
    'ImportRisks'=>'Import-Risiken',
    'ImportAssets'=>'Importieren Von Assets',
    'AssetValue'=>'Asset Value',
    'Register'=>'Registrieren',
    'RegisterSimpleRisk'=>'Registrieren SimpleRisk',
    'RegistrationText'=>'Durch eine Anmeldung SimpleRisk Sie werden die Bereitstellung Ihrer Kontaktinformationen, so dass Sie kann aktualisiert werden mit den neuesten release-Informationen und wichtige Sicherheitsbenachrichtigungen. Ihre Daten werden niemals verkauft, an Dritte. Eingetragene Instanzen auch die Fähigkeit haben, gesichert und aktualisiert mit dem Klick auf eine Schaltfläche.',
    'RegistrationInformation'=>'Registrierungs-Informationen',
    'Company'=>'Unternehmen',
    'JobTitle'=>'Job-Titel',
    'Phone'=>'Telefon',
    'UpgradeSimpleRisk'=>'Upgrade SimpleRisk',
    'UpgradeInstructions'=>'In diesem Abschnitt wird das Upgrade Extra. Um sicherzustellen, dass Sie die neueste version haben, wählen Sie "Update", neu registrieren, und laden Sie diese Seite.',
    'NoUpgradeNeeded'=>'Kein upgrade ist erforderlich in dieser Zeit.',
    'BackupDatabase'=>'Backup der Datenbank',
    'UpgradeApplication'=>'Aktualisierung der Anwendung',
    'UpgradeDatabase'=>'- Upgrade der Datenbank',
    'CustomExtras'=>'Individuelle Extras',
    'CustomExtrasText'=>'Es wäre genial, wenn das alles kostenlos, oder? Hoffentlich ist der Kern SimpleRisk Plattform ist in der Lage, zu dienen, alle Ihre Risiko-management-Anforderungen. Aber, wenn Sie finden, sich selbst noch mehr zu wollen-Funktionalität, die wir entwickelt haben, eine Reihe von "Extras", die genau das tun.',
    'Upgrade'=>'Upgrade',
    'Install'=>'Installieren',
    'Purchase'=>'Kaufen',
    'PasswordPolicy'=>'Passwort-Policy',
    'MinimumNumberOfCharacters'=>'Minimale Anzahl von Zeichen',
    'RequireAlphaCharacter'=>'Benötigen Alpha-Zeichen',
    'RequireUpperCaseCharacter'=>'Benötigen Großbuchstaben',
    'RequireLowerCaseCharacter'=>'Benötigen Kleinbuchstaben',
    'RequireNumericCharacter'=>'Erfordern Numerische Zeichen',
    'RequireSpecialCharacter'=>'Erfordern Besondere Charakter',
    'Enabled'=>'Aktiviert',
    'RiskPyramid'=>'Risiko-Pyramide',
    'RiskPyramidDescription'=>'Die Risiko-Pyramide oben zeigt die Verteilung der Risiken zwischen den verschiedenen Risikostufen. Ein top-schwere Pyramide kann ein Anzeichen sein, dass Ihre Organisation nimmt zu viel Risiko.',
    'RiskAdvice'=>'Risiko-Beratung',
    'AddDeleteAssets'=>'Hinzufügen & Löschen Von Assets',
    'EditAssets'=>'Bearbeiten Von Vermögenswerten',
    'AutomaticAssetValuation'=>'Automatische Asset Valuation',
    'ManualAssetValuation'=>'Handbuch Asset Valuation',
    'MinimumValue'=>'Minimum Wert',
    'MaximumValue'=>'Maximale Wert',
    'ValueRange'=>'Wertebereich',
    'DefaultAssetValuation'=>'Standard-Asset Valuation',
    'Default'=>'Standard',
    'RisksAndAssets'=>'Risiken und Vermögenswerte',
    'Report'=>'Bericht',
    'RisksByAsset'=>'Risiken von Asset',
    'AssetsByRisk'=>'Vermögenswerte von Risiko',
    'MaximumQuantitativeLoss'=>'Maximale Quantitative Verlust',
    'MitigationOwner'=>'Klimaschutz Besitzer',
    'MitigationCost'=>'Klimaschutz Kosten',
    'RiskColumns'=>'Risiko-Spalten',
    'MitigationColumns'=>'Klimaschutz Spalten',
    'ReviewColumns'=>'Überprüfen Sie Spalten',
    'ChangeStatus'=>'Status Ändern',
    'SetRiskStatusTo'=>'Set-Risiko-Status Zu',
    'AddNewStatusNamed'=>'Hinzufügen neuer status benannt',
    'DeleteStatusNamed'=>'Löschen status benannt',
    'DefaultCurrencySymbol'=>'Standard-Währungssymbol',
    'DefaultValues'=>'Default-Werte',
    'RiskSource'=>'Risiko Quelle',
    'AddNewSourceNamed'=>'Fügen Sie neue Quelle benannt',
    'DeleteSourceNamed'=>'Löschen Quelle benannt',
    'CheckAllAssessments'=>'Prüfen Alle Bewertungen Anzeigen',
    'AllowAccessToAssessmentsMenu'=>'Zugriff gestatten auf "Bewertungen" - Menü',
    'Assessments'=>'Bewertungen',
    'AvailableAssessments'=>'Verfügbar Bewertungen',
    'PendingRisks'=>'Ausstehende Risiken',
    'CreateAssessment'=>'Erstellen Bewertung',
    'EditAssessment'=>'Bearbeiten Bewertung',
    'Overview'=>'Übersicht',
    'OpenVsClosed'=>'Offene vs Geschlossen',
    'MitigatedVsUnmitigated'=>'Gemildert vs Ungemilderte',
    'ReviewedVsUnreviewed'=>'Überprüft, vs ungeprüft,',
    'OpenedRisks'=>'Eröffnet Risiken',
    'MailSettings'=>'E-Mail-Einstellungen',
    'TransportAgent'=>'Der Transport-Agent',
    'FromName'=>'Vom Namen',
    'FromEmail'=>'Von E-Mail',
    'ReplyToName'=>'ReplyTo-Namen',
    'ReplyToEmail'=>'ReplyTo E-Mail',
    'Host'=>'Host',
    'SMTPAuthentication'=>'SMTP-Authentifizierung',
    'Encryption'=>'Verschlüsselung',
    'Port'=>'Hafen',
    'Next'=>'Weiter',
    'NewAssessmentQuestion'=>'Neue Bewertung Frage',
    'Question'=>'Frage',
    'RiskScore'=>'Risiko-Score',
    'SubmitRisk'=>'Senden Risiko',
    'Answer'=>'Antwort',
    'AddQuestion'=>'Frage Hinzufügen',
    'SaveAssessment'=>'Sparen Bewertung',
    'SendAssessment'=>'Senden Bewertung',
    'DeleteAssessment'=>'Löschen Sie Die Bewertung',
    'AssessmentName'=>'Bewertung Name',
    'SendTo'=>'Senden',
    'ActiveAssessments'=>'Aktiv Bewertungen',
    'SentTo'=>'Gesendet',
    'From'=>'Aus',
    'Key'=>'Schlüssel',
    'GoToSSOLoginPage'=>'SSO-Login-Seite gehen',
    'APIKey'=>'API-Schlüssel',
    'GenerateAPIKey'=>'API-Schlüssel zu generieren',
    'RotateAPIKey'=>'API-Schlüssel drehen',
    'InvalidateAPIKey'=>'API-Key ungültig',
    'Deactivate'=>'Deaktivieren',
    'ImportExportExtra'=>'Import-Export Extra',
    'SaveDetails'=>'Speichern Details',
    'ClearForm'=>'Klare Form',
    'SaveMitigation'=>'Minderung zu retten',
    'Cancel'=>'Abbrechen',
    'SubmitReview'=>'Beitrag einreichen',
    'UnassignedRisks'=>'Nicht zugewiesene Risiken',
    'DisableRegistrationNotice'=>'Deaktivieren der Registrierungshinweis',
    'UserPolicy'=>'Benutzerrichtlinie',
    'UseCaseSensitiveValidationOfUsername'=>'Verwenden Sie Groß-/Kleinschreibung Validierung des Benutzernamens',
    'MitigationPlanning'=>'Datum der geplanten Mitigation',
    'AssetDetails'=>'Asset-Details',
    'RiskList'=>'Risikoliste',
    'Are you sure you want to close the risk? All changes will be lost!'=>'Sind Sie sicher, dass Sie das Risiko schließen möchten? Alle Änderungen werden verloren gehen!',
    'MinimumPasswordAge' => 'Minimales Kennwortalter',
    'MaximumPasswordAge' => 'Maximales Kennwortalter',
    'MaximumAttemptsLockout' => 'Maximale Versuche Lockout',
    'MaximumAttemptsLockoutTime' => 'Maximale Versuche Sperrzeit',
    'attempts' => 'Versuche',
    'minutes' => 'Minuten',
    'AccountLockedOut' => 'Konto gesperrt',
    'AccountLockoutPolicy' => 'Kontosperrungsrichtlinien',
    'ImportExportIsDeactivated' => 'Import-Export ist deaktiviert',
    'PurchaseTheExtra' => 'Die Extra zu kaufen',
    'ExpandAll' => 'Alle aufklappen',
    'ConditionMessageForMinChar' => 'Passwort sollte das Minimum der $min_chars Zeichen enthalten.',
    'ConditionMessageForAlpha' => 'Passwort sollte einen Buchstaben enthalten.',
    'ConditionMessageForUppercase' => 'Passwort sollte ein Großbuchstabe enthalten.',
    'ConditionMessageForLowercase' => 'Passwort sollte ein Kleinbuchstaben enthalten.',
    'ConditionMessageForDigit' => 'Passwort muss eine Ziffer enthalten.',
    'ConditionMessageForSpecialchar' => 'Passwort sollte ein Sonderzeichen enthalten.',
    'ConditionMessageForMinPasswordAge' => 'Passwort kann aktualisiert werden, von zuletzt aktualisiert: Mal das Minimum an $min_password_age Tage später.',
    'TrustedDomains' => 'Vertrauenswürdige Domänen',
    'SimpleRiskColumnMapping' => 'SimpleRisk Spaltenzuordnung',
    'Mapping' => 'Zuordnung',
    'Optional' => 'Optional',
    'SaveMappingAs' => 'Speichern Sie die Zuordnung als',
    'EncryptionLevel' => 'Verschlüsselungsstufe',
    'Level' => 'Ebene',
    'Description' => 'Beschreibung',
    'ShowRiskScoreOverTime' => 'Risiko-Score im Laufe der Zeit zu zeigen',
    'HideRiskScoreOverTime' => 'Risiko-Score im Laufe der Zeit zu verstecken',
    'ReviewRegularlyHelp' => 'Unten ist die Liste aller Risiken sortiert nach Unreviewed letzten fällig und das Datum der nächsten Überprüfung',
    'RiskScoringHistory' => 'Risiko-Scoring-Geschichte',
    'RiskAddPermissionMessage' => 'Sie sind nicht berechtigt, neue Risiken vorzulegen.  Alle Risiken, die Sie versuchen, senden werden nicht aufgezeichnet werden.  Kontaktieren Sie einen Administrator wenn Sie das Gefühl, dass Sie diese Nachricht irrtümlich erreicht haben.',
    'SubjectRiskCannotBeEmpty' => 'Das Thema eines Risikos darf nicht leer sein.',
    'InvalidRiskID' => 'Ungültige Risiko-ID',
    'Success' => 'Erfolg',
    'RiskUpdatePermissionMessage' => 'Sie haben keine Berechtigung zum Ändern von Risiken.  Alle Risiken, die Sie versuchen, ändern werden nicht aufgezeichnet werden.  Kontaktieren Sie einen Administrator wenn Sie das Gefühl, dass Sie diese Nachricht irrtümlich erreicht haben.',
    'RiskReviewPermission' => 'Sie sind nicht berechtigt, $risk_level Ebene Risiken zu überprüfen.  Alle Bewertungen, die Sie versuchen, senden werden nicht aufgezeichnet werden.  Kontaktieren Sie einen Administrator wenn Sie das Gefühl, dass Sie diese Nachricht irrtümlich erreicht haben.',
    'DateAndTime' => 'Datum und Uhrzeit',
    'mCryptWarning' => 'Die "Mcrypt" Erweiterung muss installiert sein, damit die Verschlüsselung Extra um richtig zu arbeiten.  Bitte installieren Sie es weiterhin.',
    'APIInCompatibleWithEncryptionLevel' => 'Die API ist nicht kompatibel mit der Benutzerebene Verschlüsselung verschlüsselt Datenbank extra.',
    'UnauthenticatedAccessInAPI' => 'Nicht authentifizierter Zugriff.  Bitte melden Sie sich an oder bieten einen Schlüssel, um die SimpleRisk-API verwenden.',
    'ResetPasswordMessageInUserLevelEncryption' => 'Das Kennwort kann nicht zurückgesetzt werden, da dieser Benutzer im Benutzer-Verschlüsselung ist. Kontaktieren Sie bitte den Administrator.',
    'YouNeedToSpecifyAnIdParameter' => 'Sie müssen einen ID-Parameter angeben.',
    'NoMitigation' => 'Es gibt keine festgelegten Abschwächung.',
    'NoReview' => 'Es gibt keine festgelegten Beitrag.',
    'MysqldumpPathWasSavedSuccessfully'=>'Mysqldump-Pfad wurde erfolgreich gespeichert.',
    'UnavailableMysqldumpService'=>'Es gibt keine Avaiable "Mysqldump" Service auf Server. Setzen Sie absolute Mysqldump Wirtschaftsweg.',
    'AllOpenRisksByTeamByLevel'=>'Alle offenen Risiken durch Team von Risikostufe',
    '' => '',
);

?>
