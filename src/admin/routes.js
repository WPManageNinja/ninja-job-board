import GlobalView from './Components/Global/index.vue';
import AllForms from './Components/Forms/AllForms.vue';

import EditFormView from './Components/Form/index.vue';
import FormBuilder from './Components/Form/FormBuilder.vue';
import FormSettingsIndex from './Components/Form/settings/index.vue';
import FormConfirmationSettings from './Components/Form/settings/ConfirmationSettings.vue'
import FormSchedulingSetting from './Components/Form/settings/SchedulingSettings.vue';
import AllCampaigns from './Components/Form/MassEmail/MassEmail.vue'
import NewCampaign from './Components/Form/MassEmail/NewCampaign.vue'
import ViewCampaign from './Components/Form/MassEmail/ViewCampaign.vue'


import FormEmailSettings from './Components/Form/EmailSettings/FormEmailSettings.vue';
import FormEntries from './Components/Form/FormEntries/FormEntries.vue';

import Entries from './Components/Entries/Entries.vue';
import Entry from './Components/Form/FormEntries/Entry.vue';
import SettingView from './Components/Settings/index.vue'
import GeneralSettings from './Components/Settings/GeneralSettings.vue'
import FormDesignSettings from './Components/Form/settings/FormDesignSettings.vue'
import GlobalTools from './Components/Settings/GlobalTools.vue'


export function getRoutes() {
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
    }
]);
const formEditRoutes = window.WPJobBoard.applyFilters('wpf_edit_children_roues',[
    {
        // A job opens on its applications
        path: '',
        redirect: to => ({ name: 'form_entries', params: to.params })
    },
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

return [
    {
        path: '/',
        component: GlobalView,
        props: true,
        children: [
            {
                path: '',
                name: 'forms',
                component: AllForms
            },
            {
                path: 'entries',
                name: 'entries',
                component: Entries
            },
            {
                path: 'settings',
                component: SettingView,
                children: globalSettingsViewChilderRoutes
            },
        ]
    },
    {
        path: '/edit-form/:form_id',
        component: EditFormView,
        props: true,
        children: formEditRoutes
    }
];
}
