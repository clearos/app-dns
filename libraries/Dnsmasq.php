<?php

/**
 * Dnsmasq DNS class.
 *
 * @category   apps
 * @package    dns
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dns/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\dns;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('dns');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Dnsmasq DNS class.
 *
 * @category   apps
 * @package    dns
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dns/
 */


class Dnsmasq extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/dnsmasq.conf';
    const DEFAULT_PORT = 53;

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Dnsmasq constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('dnsmasq');
    }

    /**
     * Returns port used by DNS server.
     *
     * @return integer port number
     * @throws Engine_Exception
     */

    public function get_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = $this->_load_config();

        if (isset($config['port']))
            return $config['port'];
        else
            return self::DEFAULT_PORT;
    }

    /**
     * Sets port used by DNS server.
     *
     * Setting this port to 0 will disable the DNS server.
     *
     * @param integer $port port number
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_port($port));

        $this->_set_parameter('port', $port);
    }

    /**
     * Sets state.
     *
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($state)
            $this->_set_parameter('port', self::DEFAULT_PORT);
        else
            $this->_set_parameter('port', 0);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates port.
     *
     * @param boolean $port port number
     *
     * @return string error message if port is invalid
     */

    public function validate_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($port === 0)
            return;

        if (! Network_Utils::is_valid_port($port))
            return lang('network_port_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration file.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_loaded)
            return $this->config;

        $dnsmasq_file = new File(self::FILE_CONFIG);

        $lines = $dnsmasq_file->get_contents_as_array();

        foreach ($lines as $line) {
            $matches = array();

            if (preg_match("/^#/", $line) || preg_match("/^\s*$/", $line)) {
                continue;
            } else if (preg_match("/(.*)=(.*)/", $line, $matches)) {
                $key = $matches[1];
                $value = $matches[2];
            } else {
                $key = trim($line);
                $value = "";
            }

            $this->config[$key] = $value;
        }

        $this->is_loaded = TRUE;

        return $this->config;
    }

    /**
     * Sets a parameter in the config file.
     *
     * @param string $key   name of the key in the config file
     * @param string $value value for the key
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        $match = $file->replace_lines("/^$key\s*=/", "$key=$value\n");

        if ($match === 0) {
            $match = $file->replace_lines("/^#\s*$key\s*=/", "$key=$value\n");
            if ($match === 0)
                $file->add_lines("$key=$value\n");
        }

        $this->is_loaded = FALSE;
    }
}
