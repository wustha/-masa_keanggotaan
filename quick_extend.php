<?php
/**
 * Copyright (C) 2018 Drajat Hasan (drajathasan20@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* Quick extend */

// key to authenticate
define('INDEX_AUTH', '1');
// key to get full database access
define('DB_ACCESS', 'fa');

if (!defined('SB')) {
  // main system configuration
  require '../../../sysconfig.inc.php';
  // start the session
  require SB.'admin/default/session.inc.php';
}
// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-membership');

require SB.'admin/default/session_check.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

if (isset($_POST['member_id'])) {
  $member_id = $dbs->escape_string($_POST['member_id']);
  // Check for is expire or not
  $expire_check = $dbs->query("SELECT member_id, expire_date FROM member WHERE member_id = '".$member_id."' AND TO_DAYS('".date('Y-m-d')."') > TO_DAYS( expire_date )");
  
  if ($expire_check->num_rows == 1) {
    if (trim($_POST['extend_date']) == 'one_year') {
      // Fetch data
      $extend_d = $expire_check->fetch_row();
      // Extend the membership for one year or not
      $next_year     = date('Y-m-d',strtotime(date("Y-m-d", time()) . " + 1 year"));
      // Extend 1+ year with last expire date data.
      if (trim($_POST['special_opt']) == 'with_before_exp_date') {
         // Filter by year
         $year = substr($extend_d[1], 0, 4);
         if ($year < date('Y')) {
            $next_year  = date('Y-m-d', strtotime('+1 year '.date('Y-m-d').''));
         } else {
            $next_year  = date('Y-m-d', strtotime('+1 year '.$extend_d[1].''));
         }
      }
      $extend_member = 'UPDATE member SET expire_date = "'.$next_year.'" WHERE member_id = "'.$member_id.'"';
    } else {
      $next_year     = $dbs->escape_string($_POST['extend_date']);
      $extend_member = 'UPDATE member SET expire_date = "'.$next_year.'" WHERE member_id = "'.$member_id.'"';
    }
    // Extend
    $extend_data = $dbs->query($extend_member);

    if ($extend_data) {
       echo "1|success";
    } else {
      echo "2|".$dbs->error;
    }
    exit();
  } else {
    echo "0|Data tidak ditemukan dengan id : ".$_POST['member_id'];
  }
  exit();
}
?>
<style type="text/css">
	.dateField {
		width: 250px;
    display: none;
	}

  #resultLayer {
    padding: 10px;
  }
</style>
<fieldset class="menuBox">
<div class="menuBoxInner quickReturnIcon">
    <div class="per_title">
	    <h2><?php echo 'Perpanjang Cepat'; ?></h2>
    </div>
    <div class="sub_section">
	    <div class="action_button">
		    <?php echo 'Masukan ID anggota yang ingin diperpanjang masa keaktifannya'; ?>
	    </div>
	    <?php echo 'Jangka Waktu Perpanjangan'; ?> :
        <br>&nbsp;
        <input type="checkbox" id="extend_today" value="today"/><label>&nbsp;dengan tanggal hari ini.</label>
      	<select id="date" style="display: block;">
      		<option value="dafault">1 Tahun</option>
      		<option value="defined">Terdefinisikan</option>
      	</select>
      	<?php echo simbio_form_element::dateField('expDate', '');?>
      	<br>
      	<?php echo __('Member ID'); ?> :
      	<input type="text" name="quickExtendID" id="quickExtendID" size="30" />
    </div>
</div>
</fieldset>
<div id="resultLayer" style="font-size: 15pt;">&nbsp;</div>
<script type="text/javascript">
    // Date event change
    $('#date').on('change', function(){
      // If defined
      if ($(this).val() == 'defined') {
        $('.dateField').slideDown('slow');
        $('#expDate').focus();
        $('#extend_today').attr('checked', false);
      } else {
        $('.dateField').slideUp('slow');
        $('#extend_today').attr('checked', 'true');
        $('#quickExtendID').focus();
      }
    });

    // Textbox keyup
    $('#quickExtendID').keyup(function(e){
        // Enter event
        if (e.keyCode == 13) {
          // Global variabel
          var extend = 'one_year';
          var member_id = $('#quickExtendID').val();
          var spec_date = $('#extend_today:checked').val();
          // check the extend date
          if ($('#date').val() == 'defined') {
            var extend = $('#expDate').val();
          }

          // Defined Date
          if (spec_date != 'today') {
              var spec_date = 'with_before_exp_date';
          }

          // post
          $.post("<?php echo $_SERVER['PHP_SELF'];?>", 
                {
                  'extend_date':extend, 
                  'member_id':member_id, 
                  'special_opt':spec_date
                }, 

          // Set Result
          function(result) {
            var result_layer = $('#resultLayer');
            var result = result.split("|");
            // Debugging 
            // result_layer.html(spec_date+' '+result);
            if (result[0] == 1) {
                result_layer.slideDown('slow');
                result_layer.addClass('bg-success text-success');
                result_layer.html('Proses perpajangan masa keanggotaan dengan id : '+member_id+' telah berhasil.');
            } else if (result[0] == 2) {
                result_layer.slideDown('slow'); 
                result_layer.addClass('bg-danger text-danger');
                result_layer.html(result[1]);
            } else {
                result_layer.slideDown('slow'); 
                result_layer.addClass('bg-danger text-danger');
                result_layer.html(result[1]);
            }
            // Set Timeout
            setTimeout(
                    function() { 
                       result_layer.slideUp('slow');
                       $('#quickExtendID').val("");
                    }, 
            5000);
          });
        }
    });
</script>