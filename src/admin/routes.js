import SupportAndDocumentation from './Components/SupportAndDocumentation';
import GlobalView from './Components/Global/index';
import AllForms from './Components/Forms/AllForms';

import EditFormView from './Components/Form/index';
import FormBuilder from './Components/Form/FormBuilder';
import FormSettingsIndex from './Components/Form/settings/index';
import FormConfirmationSettings from './Components/Form/settings/ConfirmationSettings'
import FormSchedulingSetting from './Components/Form/settings/SchedulingSettings';
import FormCustomCssJs from './Components/Form/settings/FormCustomCssJs';
import AllCampaigns from './Components/Form/MassEmail/MassEmail'
import NewCampaign from './Components/Form/MassEmail/NewCampaign'
import ViewCampaign from './Components/Form/MassEmail/ViewCampaign'


import FormEmailSettings from './Components/Form/EmailSettings/FormEmailSettings';
import FormEntries from './Components/Form/FormEntries/FormEntries';

import Entries from './Components/Entries/Entries';
import Entry from './Components/Form/FormEntries/Entry';
import SettingView from './Components/Settings/index'
import GeneralSettings from './Components/Settings/GeneralSettings'
import FormDesignSettings from './Components/Form/settings/FormDesignSettings'
import GlobalTools from './Components/Settings/GlobalTools'


const globalSettingsViewChilderRoutes = window.WPJobBoard.applyFilters('wpf_global_settings_childern_routes', [
    {
        name: 'general_settings',
        path: 'general-settings',
        component: GeneralSettings
    },
    {
        name: 'tools',
        path: 'tools',
        component: GlobalTools
    }
]);
const formEditorChildrenRoutes = window.WPJobBoard.applyFilters('wpf_main_children_roues', [
    {
        path: 'confirmation_settings',
        name: 'confirmation_settings',
        component: FormConfirmationSettings
    },
    {
        path: 'design_options',
        name: 'design_options',
        component: FormDesignSettings
    },
    {
        path: 'scheduling_settings',
        name: 'scheduling_settings',
        component: FormSchedulingSetting
    },
    {
        path: 'custom-css-js',
        name: 'custom_css_js',
        component: FormCustomCssJs
    }
]);
const formEditRoutes = window.WPJobBoard.applyFilters('wpf_edit_children_roues',[
    {
        path: 'form-builder',
        name: 'edit_form',
        component: FormBuilder
    },
    {
        path: 'settings',
        component: FormSettingsIndex,
        children: formEditorChildrenRoutes
    },
    {
        path: 'email_settings',
        name: 'email_settings',
        component: FormEmailSettings
    },
    {
        path: 'form_entries',
        name: 'form_entries',
        component: FormEntries
    },
    {
        path: 'entries/:entry_id/view',
        name: 'entry',
        component: Entry
    },
    {
        path: 'email_broadcast',
        name: 'email_campaigns',
        component: AllCampaigns
    },
    {
        path: 'email_broadcast/new',
        name: 'new_email_campaign',
        component: NewCampaign
    },
    {
        path: 'email_broadcast/:campaign_id/view',
        name: 'view_email_campaign',
        component: ViewCampaign
    }
]);

export const routes = [
    {
        path: '/',
        component: GlobalView,
        props: true,
        children: [
            {
                path: '/',
                name: 'forms',
                component: AllForms
            },
            {
                path: 'entries',
                name: 'entries',
                component: Entries
            },
            {
                path: '/settings/',
                component: SettingView,
                children: globalSettingsViewChilderRoutes
            },
            {
                path: '/support',
                name: 'support',
                component: SupportAndDocumentation
            }
        ]
    },
    {
        path: '/edit-form/:form_id/',
        component: EditFormView,
        props: true,
        children: formEditRoutes
    }
];