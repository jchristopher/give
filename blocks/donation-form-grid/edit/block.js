/**
 * External dependencies
 */
import { isEmpty, pickBy, isUndefined } from 'lodash';
import { stringify } from 'querystringify';

/**
 * Wordpress dependencies
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
import FormGridPreview from './components/preview';

/**
 * Render Block UI For Editor
 */

const GiveDonationFormGrid = ( props ) => {
	const { formGridData } = props;

	// Render block UI
	let blockUI;

	if ( null === formGridData ) {
		blockUI = <GiveBlankSlate title={ __( 'Loading...' ) } isLoader={ true } />;
	} else if ( isEmpty( formGridData ) ) {
		blockUI = <NoForms />;
	} else {
		blockUI = <FormGridPreview
			html={ formGridData }
			{ ... { ...props } } />;
	}

	return ( <div className={ props.className } key="GiveDonationFormGridBlockUI">{ blockUI }</div> );
};

const actions = {
	setFormGrid( formGridData ) {
		return {
			type: 'SET_FORM_GRID',
			formGridData,
		};
	},

	getFormGrid( path ) {
		return {
			type: 'RECEIVE_FORM_GRID',
			path,
		};
	},
};

const store = registerStore( 'give/donation-form-grid', {
	reducer( state = { formGridData: null }, action ) {

		switch ( action.type ) {
			case 'SET_FORM_GRID':
				return {
					...state,
					formGridData: action.formGridData,
				};
			case 'RECEIVE_FORM_GRID':
				return action.formGridData;
		}

		return state;
	},

	actions,

	selectors: {
		getFormGrid( state ) {
			const { formGridData } = state;
			return formGridData;
		},
	},

	resolvers: {
		async getFormGrid( parameters ) {
			const formGridData = await wp.apiRequest( { path: `/give-api/v2/form-grid/?${ parameters }` } );
			dispatch( 'give/donation-form-grid' ).setFormGrid( formGridData );
		},
	},

} );

/**
 * Export component attaching withSelect
 */
export default withSelect( ( select, props ) => {
	const { columns, showGoal, showExcerpt, showFeaturedImage, displayType } = props.attributes;

	const parameters = stringify( pickBy( {
		columns: columns,
		show_goal: showGoal,
		show_excerpt: showExcerpt,
		show_featured_image: showFeaturedImage,
		display_type: displayType,
		},
		value => ! isUndefined( value )
	) );

	return {
		formGridData: select( 'give/donation-form-grid' ).getFormGrid( parameters )
	}
})( GiveDonationFormGrid )
