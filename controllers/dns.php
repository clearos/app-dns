<?php

/**
 * Local DNS server controller.
 *
 * @category   Apps
 * @package    DNS
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dns/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Local DNS server controller.
 *
 * @category   Apps
 * @package    DNS
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dns/
 */

class Dns extends ClearOS_Controller
{
    /**
     * Local DNS summary view.
     *
     * @return view   
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('network/Hosts');
        $this->lang->load('dns');

        // Load view data
        //---------------

        try {
            $data['hosts'] = $this->hosts->get_entries();
        } catch (Engine_Exception $e) {
            $this->page->view_exception($e->get_message());
            return;
        }
 
        // Load views
        //-----------

        $this->page->set_title(lang('dns_local_dns_server'));

        $this->load->view('theme/header');
        $this->load->view('dns/summary', $data);
        $this->load->view('theme/footer');
    }

    /**
     * Add local DNS entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function add($ip = NULL)
    {
        // Use common add/edit form
        $this->_addedit($ip, 'add');
    }

    /**
     * Delete local DNS entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function delete($ip = NULL)
    {
        // Load libraries
        //---------------

        $this->lang->load('dns');

        // Load views
        //-----------

        $this->page->set_title(lang('dns_dns_entry'));

        $data['message'] = sprintf(lang('dns_confirm_delete'), $ip);
        $data['ok_anchor'] = '/app/dns/destroy/' . $ip;
        $data['cancel_anchor'] = '/app/dns';
    
        $this->load->view('theme/header');
        $this->load->view('theme/confirm', $data);
        $this->load->view('theme/footer');
    }

    /**
     * Edit DNS entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function edit($ip = NULL)
    {
        // Use common add/edit form
        $this->_addedit($ip, 'edit');
    }

    /**
     * Destroys local DNS entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function destroy($ip = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Hosts');
        $this->load->library('dns/DnsMasq');

        // Handle form submit
        //-------------------

        try {
            $this->hosts->delete_entry($ip);
            $this->dnsmasq->reset();
            $this->page->set_success(lang('base_deleted'));
        } catch (Engine_Exception $e) {
            $this->page->view_exception($e->get_message());
            return;
        }

        // Redirect
        //---------

        redirect('/dns');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * DNS entry rommon add/edit form handler.
     *
     * @param string $ip        IP address
     * @param string $form_type form type
     *
     * @return view
     */

    function _addedit($ip, $form_type)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Hosts');
        $this->load->library('dns/DnsMasq');
        $this->lang->load('dns');
        $this->lang->load('network');

        // Set validation rules
        //---------------------

        // TODO: Review the messy alias1/2/3 handling
        $this->form_validation->set_policy('ip', 'network/Hosts', 'validate_ip', TRUE);
        $this->form_validation->set_policy('hostname', 'network/Hosts', 'validate_hostname', TRUE);
        $this->form_validation->set_policy('alias1', 'network/Hosts', 'validate_alias');
        $this->form_validation->set_policy('alias2', 'network/Hosts', 'validate_alias');
        $this->form_validation->set_policy('alias3', 'network/Hosts', 'validate_alias');
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {

            $ip = $this->input->post('ip');
            $hostname = $this->input->post('hostname');
            $aliases = array();

            if ($this->input->post('alias1'))
                $aliases[] = $this->input->post('alias1');

            if ($this->input->post('alias2'))
                $aliases[] = $this->input->post('alias2');

            if ($this->input->post('alias3'))
                $aliases[] = $this->input->post('alias3');

            try {
                if ($form_type === 'edit') 
                    $this->hosts->EditEntry($ip, $hostname, $aliases);
                else
                    $this->hosts->AddEntry($ip, $hostname, $aliases);

                $this->dnsmasq->Reset();

                // Return to summary page with status message
                $this->page->set_success(lang('base_system_updated'));
                redirect('/dns');
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message(), $view);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            if ($form_type === 'edit') 
                $entry = $this->hosts->get_entry($ip);
        } catch (Engine_Exception $e) {
            $this->page->view_exception($e->get_message(), $view);
            return;
        }

        $data['form_type'] = $form_type;

        $data['ip'] = $ip;
        $data['hostname'] = isset($entry['hostname']) ? $entry['hostname'] : '';
        $data['aliases'] = isset($entry['aliases']) ? $entry['aliases'] : '';

        // Load the views
        //---------------

        $this->page->set_title(lang('dns_dns_entry'));

        $this->load->view('theme/header');
        $this->load->view('dns/add_edit', $data);
        $this->load->view('theme/footer');
    }
}
