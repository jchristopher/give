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
import DonorWallPreview from './components/preview';

/**
 * Render Block UI For Editor
 */

const GiveDonorWall = ( props, walls ) => {

	const { donorWallData } = props;

	// Render block UI
	let blockUI;

	if ( null === donorWallData ) {
		blockUI = <GiveBlankSlate title={ __( 'Loading...' ) } isLoader={ true } />;
	} else if ( isEmpty( donorWallData ) ) {
		blockUI = <p>{ __( 'No donors available...' ) }</p>;
	} else {
		blockUI = <DonorWallPreview
			html={ data }
			{ ... { ...props } } />;
	}

	return ( <div className={ props.className } key="GiveDonorWallBlockUI">{ blockUI }</div> );
};

const actions = {
	setDonorWall( donorWallData ) {
		return {
			type: 'SET_DONOR_WALL',
			donorWallData,
		};
	},

	getDonorWall( path ) {
		return {
			type: 'RECEIVE_DONOR_WALL',
			path,
		};
	},
};

const store = registerStore( 'give/donor-wall', {
	reducer( state = { donorWallData: null }, action ) {

		switch ( action.type ) {
			case 'SET_DONOR_WALL':
				return {
					...state,
					donorWallData: action.donorWallData,
				};
			case 'RECEIVE_DONOR_WALL':
				return action.donorWallData;
		}

		return state;
	},

	actions,

	selectors: {
		getDonorWall( state ) {
			const { donorWallData } = state;
			return donorWallData;
		},
	},

	resolvers: {
		async getDonorWall( parameters ) {
			const donorWallData = await wp.apiRequest( { path: `/give-api/v2/donor-wall/?${ parameters }` } );
			dispatch('give/donor-wall').getDonorWall( donorWallData );
		},
	},

} );

/**
 * Export component attaching withSelect
 */
export default withSelect( ( select, props ) => {
	const { columns, showAvatar, showName, showTotal, showDate, showComments } = props.attributes;

	const parameters = stringify( pickBy( {
		columns: columns,
		show_avatar: showAvatar,
		show_name: showName,
		show_total: showTotal,
		show_time: showDate,
		show_comments: showComments,
		},
		value => ! isUndefined( value )
	) );

	return {
		donorWallData: select( 'give/donor-wall' ).getDonorWall( parameters )
	}
})( GiveDonorWall )
