<?php
/**
 * Availability
 *
 * @package     MDJM
 * @subpackage  Classes/Availability Checker
 * @copyright   Copyright (c) 2016, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MDJM_Availability_Checker Class
 *
 * @since	1.3
 */
class MDJM_Availability_Checker {

	/**
	 * The date to check
	 *
	 * @since	1.3
	 */
	public $date = 0;
	
	/**
	 * The employees to check
	 *
	 * @since	1.3
	 */
	public $employees;
	
	/**
	 * The employee roles to report on
	 *
	 * @since	1.3
	 */
	public $roles;
	
	/**
	 * The event status' to report on
	 *
	 * @since	1.3
	 */
	public $status;
	
	/**
	 * The availability check result
	 *
	 * @since	1.3
	 */
	public $result;
	
	/**
	 * Get things going
	 *
	 * @since	1.3
	 */
	public function __construct( $date = false, $_employees = array(), $_roles = array(), $_status=array() ) {		
		return $this->setup_check( $date, $_employees, $_roles, $_status );
	} // __construct
		
	/**
	 * Setup the availability checker.
	 *
	 * @since	1.3
	 * @param	str		$date	The date to check
	 * @return	bool
	 */
	public function setup_check( $date, $_employees, $_roles, $_status )	{
		if( empty( $date ) )	{
			$date = date( 'Y-m-d' );
		}
		
		$this->date		 = ( ! empty( $date ) )		  ? strtotime( $date ) : date( 'Y-m-d' );
		
		if( empty( $_employees ) && ! empty( $_roles ) )	{
			$theemployees = mdjm_get_employees( array_merge( array( 'administrator' ), $_roles ) );
		}
		elseif( empty( $_employees ) )	{
			$theemployees = mdjm_get_employees( array_merge( array( 'administrator' ), mdjm_get_roles( false ) ) );
		}
		else	{
			$theemployees = is_array( $_employees ) ? $_employees : $_employees;
		}
		
		$employees = array();
		foreach( $theemployees as $employee )	{
			if( is_object( $employee ) )	{
				$employees[] = $employee->ID;
			}
			else	{
				$employees[] = $employee;
			}
		}
				
		$this->employees	= $employees;
		$this->roles		= ( ! empty( $_roles ) )	 ? $_roles	: mdjm_get_roles();
		$this->status	   = ( ! empty( $_status ) )	? $_status   : mdjm_get_option( 'availability_status', 'any' );
		
		if( ! is_array( $this->roles ) )	{
			array( $this->roles );
		}
		if( ! is_array( $this->status ) )	{
			array( $this->status );
		}
		
		return true;
	} // setup_check
	
	/**
	 * Perform the availability lookup.
	 *
	 * @since	1.3
	 * @param
	 * @return	bool
	 */
	public function perform_lookup()	{
		foreach( $this->employees as $employee )	{
			if( ! $this->employee_has_event( $employee ) )	{
				$this->result['available'][] = $employee;
			}
			else	{
				$this->result['unavailable'][] = $employee;
			}
		}
				
		if( ! empty( $this->result['available'] ) )	{
			return true;
		}
		
		return false;
	} // perform_lookup
	
	/**
	 * Determine if the employee has an event on the given day.
	 *
	 * @since	1.3
	 * @param	int		$employee	The employee ID
	 * @param	int		$date		The date
	 * @return	bool	True if the employee has an event, or false
	 */
	public function employee_has_event( $employee_id )	{
		return mdjm_employee_is_working( $this->date, $employee_id, $this->status );
	} // employee_has_event
	
	/**
	 * Determine if the employee has vacation on the given day.
	 *
	 * @since	1.3
	 * @param	int		$employee	The employee ID
	 * @param	int		$date		The date
	 * @return	bool	True if the employee has vacation, or false
	 */
	public function employee_has_vacation( $employee_id )	{
		return mdjm_employee_is_on_vacation( $this->date, $this->employee );
	} // employee_has_vacation
} // class MDJM_Availability_Checker