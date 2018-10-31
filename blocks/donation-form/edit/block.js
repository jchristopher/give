/**
 * External dependencies
 */
import { isEmpty, pickBy, isUndefined } from 'lodash';
import { stringify } from 'querystringify';

/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;
const {
	withSelect,
	registerStore,
	dispatch
} = wp.data;

/**
 * Internal dependencies
 */
import GiveBlankSlate from '../../components/blank-slate';
import NoForms from '../../components/no-form';
import EditForm from '../../components/edit-form';
import FormPreview from './form/preview';
import SelectForm from '../../components/select-form';

/**
 * Render Block UI For Editor
 */
const GiveForm = ( props ) => {
	const { attributes, forms, form } = props;
	const { id } = attributes;

	// Render block UI
	let blockUI;

	if ( ! id ) {
		if ( null === forms ) {
			blockUI = <GiveBlankSlate title={ __( 'Loading...' ) } isLoader={ true } />;
		} else if ( isEmpty( forms ) || || form.hasOwnProperty('error') ) {
			blockUI = <NoForms />;
		} else {
			blockUI = <SelectForm { ... { ...props } } />;
		}
	} else if ( isEmpty( form ) || form.hasOwnProperty('error') ) {
		blockUI = null === form ?
			<GiveBlankSlate title={ __( 'Loading...' ) } isLoader={ true } /> :
			<EditForm formId={ id } { ... { ...props } } />;
	} else {
		blockUI = <FormPreview
			html={ form }
			{ ... { ...props } } />;
	}

	return (
		<div className={ !! props.isSelected ? `${ props.className } isSelected` : props.className } key="GiveBlockUI">
			{ blockUI }
		</div>
	);
};

const actions = {
	setDonationForm( donationFormData ) {
		return {
			type: 'SET_DONATION_FORM',
			donationFormData,
		};
	},

	getDonationForm( path ) {
		return {
			type: 'RECEIVE_DONATION_FORM',
			path,
		};
	},

	setDonationForms( donationFormsData ) {
		return {
			type: 'SET_DONATION_FORMS',
			donationFormsData,
		};
	},

	getDonationForms( path ) {
		return {
			type: 'RECEIVE_DONATION_FORMS',
			path,
		};
	},
};

const store = registerStore( 'give/donation-form', {
	reducer( state = { donationFormData: null, donationFormsData: null }, action ) {

		switch ( action.type ) {
			case 'SET_DONATION_FORM':
				return {
					...state,
					donationFormData: action.donationFormData,
				};

			case 'SET_DONATION_FORMS':
				return {
					...state,
					donationFormsData: action.donationFormsData,
				};

			case 'RECEIVE_DONATION_FORM':
				return action.donationFormData;

			case 'RECEIVE_DONATION_FORMS':
				return action.donationFormsData;
		}

		return state;
	},

	actions,

	selectors: {
		getDonationForm( state ) {
			const { donationFormData } = state;
			return donationFormData;
		},

		getDonationForms( state ) {
			const { donationFormsData } = state;
			return donationFormsData;
		},
	},

	resolvers: {
		async getDonationForm( id, parameters ) {
			const donationFormData = await wp.apiRequest( { path: `/give-api/v2/form/${ id }/?${ parameters }` } );
			dispatch( 'give/donation-form' ).setDonationForm( donationFormData );
		},

		async getDonationForms() {
			const donationFormsData = await wp.apiRequest( { path: `/wp/v2/give_forms` } );
			dispatch( 'give/donation-form' ).setDonationForms( donationFormsData );
		},
	},

} );

/**
 * Export component attaching withSelect
 */
export default withSelect( ( select, props ) => {
	const { showTitle, showGoal, showContent, displayStyle, continueButtonTitle, id } = props.attributes;
	let parameters = {
		show_title: showTitle,
		show_goal: showGoal,
		show_content: showContent,
		display_style: displayStyle,
	};

	if ( 'reveal' === displayStyle ) {
		parameters.continue_button_title = continueButtonTitle;
	}

	parameters = stringify( pickBy( parameters, value => ! isUndefined( value ) ) );

	return {
		form: select('give/donation-form').getDonationForm(id, parameters),
		forms: select('give/donation-form').getDonationForms(),
	}
})( GiveForm )
