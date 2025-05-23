<?php
include('../../officeCheck.php');
include('../../../dbmain_conn.php');
$query1 = "select count(leaveId) as tot_leave from tblleave where status='Requested'";
$r1 = $maincon->query($query1);
$leave = $r1->fetch_column();
$query2 = "select count(complaintId) as tot_complaint from tblcomplaint where status='Unsolved'";
$r2 = $maincon->query($query2);
$complaint = $r2->fetch_column();
$query3 = "select count(rollno) as tot_delete from tbltemp";
$r3 = $maincon->query($query3);
$delete = $r3->fetch_column();
$total = $leave + $complaint + $delete;
?>
<!DOCTYPE html>



<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>Add Student</title>

  <meta name="description" content="" />

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/img/Logo/sdm_logo.png" />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

  <!-- Icons. Uncomment required icon fonts -->
  <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />

  <!-- Core CSS -->
  <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
  <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="../assets/css/demo.css" />

  <!-- Vendors CSS -->
  <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

  <!-- Page CSS -->

  <!-- Helpers -->
  <script src="../assets/vendor/js/helpers.js"></script>

  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  <script src="../assets/js/config.js"></script>
</head>
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../../../vendor/autoload.php';

function send_mail($rollno, $name, $email, $ownerName, $messEmail, $messName)
{
  $mail = new PHPMailer(true);

  try {
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;            
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'logincode22@gmail.com';
    $mail->Password   = 'qrvdbksguyxlldhb';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;


    $mail->setFrom('logincode22@gmail.com', 'LoginCode'); //from
    $mail->addAddress($email, $name);     //To

    $pass = mt_rand(1000, 9999);
    $mail->Subject = "SDM Paying Guest Student Account Credentials";
    $mail->Body = "Name: " . $name . " \nUsername: " . $email . "\nPassword:" . $pass;

    //Content
    // $mail->isHTML(true);                     
    // $mail->Subject = 'Here is the subject';
    // $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();

    //Clear Previous Recipients
    $mail->clearAddresses();
    $mail->Subject = '';
    $mail->Body = '';
    //To Mess Owner
    $mail->addAddress($messEmail, $ownerName);
    $mail->Subject = "SDM Paying Guest Student Admission";
    $mail->Body = "Dear Mess Owner, \n" . $name . " is admitted to your mess " . $messName;
    $mail->send();
    $str = "insert into tblstdlogin (rollno,emailid,pass) values('" . $rollno . "','" . $email . "','" . md5($pass) . "')";
    include('../../../dbmain_conn.php');
    if ($maincon->query($str)) {
      echo "<script>alert('Credentials sent to MailId')</script>";
    } else {
      echo "<script>alert('Error')</script>";
    }
  } catch (Exception $e) {
    echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}')</script>";
  }
}

if (isset($_POST['submit'])) {
  $rollno = $_POST['rollno'];
  $stdname = $_POST['stdname'];
  $class = $_POST['class'];
  $combination = $_POST['comb'];
  $fname = $_POST['fname'];
  $address = $_POST['address'];
  $contact = $_POST['contact'];
  $email = $_POST['email'];
  $pg = $_POST['pg'];

  $pgcode = explode(" ", $pg);
  $sql5 = "select * from tblstudent where rollno='" . $rollno . "'";
  $result = $maincon->query($sql5);
  if ($result->num_rows > 0) {
    echo "<script>alert('This rollno already exists')</script>";
  } else {
    if (!empty($_POST['gender'])) {
      $gender = $_POST['gender'];
      $sql = "select * from tblstudent where rollno='" . $rollno . "'";
      $result = $maincon->query($sql);
      if ($result->num_rows > 0) {
        echo "<script>alert('Student Already Exists')</script>";
      } else {
        $sql1 = "insert into tblstudent (rollno,name,class,combination,fatherName,gender,address,contactNo,emailid,messCode,messName) values('" . $rollno . "','" . $stdname . "','" . $class . "','" . $combination . "','" . $fname . "','" . $gender . "','" . $address . "','" . $contact . "','" . $email . "','" . $pgcode[0] . "','" . $pgcode[1] . "')";
        try {
          $maincon->query($sql1);
          $sql2 = "update tblmess set vacancy=vacancy-1 where code='" . $pgcode[0] . "'";
          $maincon->query($sql2);
          $sql3 = "select owner,emailid from tblmess where code='" . $pgcode[0] . "'";
          $res1 = $maincon->query($sql3);
          while ($row1 = $res1->fetch_assoc()) {
            $ownerName = $row1['owner'];
            $messEmail = $row1['emailid'];
          }
          $sql4 = "insert into tblpgstudent (rollno,aadharImg,image,messCode) values('" . $rollno . "','default.png','default.png','" . $pgcode[0] . "')";
          $maincon->query($sql4);
          send_mail($rollno, $stdname, $email, $ownerName, $messEmail, $pgcode[1]);
          echo "<script>alert('Student added successfully')</script>";
        } catch (Exception $e) {
          echo "<script>alert('Error:" . $e . "')</script>";
        }
      }
    } else {
      echo "<script>alert('Gender must be selected')</script>";
    }
  }
}

?>

<body>
  <!-- Layout wrapper -->
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      <!-- Menu -->

      <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
        <div class="app-brand demo">
          <a href="index.php" class="app-brand-link">
            <span class="app-brand-logo demo">
              <img src="../assets/img/Logo/sdm_logo.png" alt="" srcset="" style="width:40px;">
            </span>
            <span class="app-brand-text demo menu-text fw-bolder ms-2">SDM</span>
          </a>

          <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
          </a>
        </div>

        <div class="menu-inner-shadow"></div>

        <ul class="menu-inner py-1">
          <!-- Dashboard -->
          <li class="menu-item">
            <a href="index.php" class="menu-link">
              <i class="menu-icon tf-icons bx bx-home-circle"></i>
              <div data-i18n="Analytics">Dashboard</div>
            </a>
          </li>

          <!-- Layouts -->


          <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Pages</span>
          </li>
          <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bx-envelope"></i>
              <div data-i18n="Account Settings">Email Update</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item">
                <a href="account.php" class="menu-link">
                  <div data-i18n="Account">Email Update</div>
                </a>
              </li>
            </ul>
          </li>
          <!-- Notifications -->
          <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">

              <i class="menu-icon tf-icons bx bxs-bell"></i>
              <div data-i18n="Account Settings">Notifications<?php if ($total > 0) : ?><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-dot text-danger" viewBox="0 0 16 16">
                  <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" />
                </svg><?php endif; ?></div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item">
                <a href="leave.php" class="menu-link">
                  <div data-i18n="Leave">Leave<?php if ($leave > 0) : ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-dot text-danger" viewBox="0 0 16 16">
                      <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" />
                    </svg><?php endif; ?>
                  </div>
                </a>
              </li>
              <li class="menu-item">
                <a href="complaint.php" class="menu-link">
                  <div data-i18n="Complain">Complaint
                    <?php if ($complaint > 0) : ?>
                      <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-dot text-danger" viewBox="0 0 16 16">
                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" />
                      </svg><?php endif; ?>
                  </div>
                </a>
              </li>
              <li class="menu-item">
                <a href="n-delete-student.php" class="menu-link">
                  <div data-i18n="Notifications">Delete Student<?php if ($delete > 0) : ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-dot text-danger" viewBox="0 0 16 16">
                      <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" />
                    </svg><?php endif; ?>
                  </div>
                </a>
              </li>
            </ul>
          </li>
          <!-- End Notifications -->



          <!-- Forms & Tables -->
          <li class="menu-header small text-uppercase"><span class="menu-header-text">Operations</span></li>
          <!-- Forms -->
          <li class="menu-item active open">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bxs-plus-circle "></i>

              <div data-i18n="Form Elements">Insert</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item ">
                <a href="pg-add.php" class="menu-link">
                  <div data-i18n="PG">PG</div>
                </a>
              </li>
              <li class="menu-item ">
                <a href="committee-add.php" class="menu-link">
                  <div data-i18n="Committee members">Committee members</div>
                </a>
              </li>
              <li class="menu-item active">
                <a href="student-add.php " class="menu-link">
                  <div data-i18n="Student">Student</div>
                </a>
              </li>
            </ul>
          </li>

          <!-- Forms -->
          <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bxs-edit"></i>
              <div data-i18n="Form Elements">Update</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item">
                <a href="update-pg.php" class="menu-link">
                  <div data-i18n="PG">PG</div>
                </a>
              </li>
              <li class="menu-item">
                <a href="update-committee.php" class="menu-link">
                  <div data-i18n="Committee members">Committee members</div>
                </a>
              </li>
              <li class="menu-item">
                <a href="update-student.php" class="menu-link">
                  <div data-i18n="Student">Student</div>
                </a>
              </li>
            </ul>
          </li>

          <!-- Forms -->
          <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bxs-trash-alt"></i>
              <div data-i18n="Form Elements">Delete</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item">
                <a href="delete-pg.php" class="menu-link">
                  <div data-i18n="PG">PG</div>
                </a>
              </li>
              <li class="menu-item">
                <a href="delete-committee.php" class="menu-link">
                  <div data-i18n="Committee members">Committee members</div>
                </a>
              </li>
              <li class="menu-item">
                <a href="delete-student.php" class="menu-link">
                  <div data-i18n="Student">Student</div>
                </a>
              </li>
            </ul>
          </li>

          <!-- View-->
          <li class="menu-header small text-uppercase"><span class="menu-header-text">View</span></li>
          <!-- Cards -->
          <li class="menu-item">
            <a href="view-pg.php" class="menu-link">
              <i class="menu-icon tf-icons bx bxs-building-house"></i>
              <div data-i18n="Basic">PG Review</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="view-committe.php" class="menu-link">
              <i class="menu-icon tf-icons bx bxs-group"></i>

              <div data-i18n="Basic">Committee Members</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="view-student.php" class="menu-link">
              <i class="menu-icon tf-icons bx bxs-user"></i>

              <div data-i18n="Basic">Student</div>
            </a>
          </li>


          <!-- View-->
          <li class="menu-item">
            <a href="deleted-students.php" class="menu-link">
              <i class="menu-icon fa-solid fa-file"></i>
              <div data-i18n="Basic">Deleted Students</div>
            </a>
          </li>

          <!-- View-->

          <!-- View-->
          <li class="menu-header small text-uppercase"><span class="menu-header-text">NOTICE </span></li>
          <li class="menu-item">
            <a href="notice.php" class="menu-link">
              <i class="menu-icon fa-regular fa-envelope-open"></i>
              <div data-i18n="Basic">Notice</div>
            </a>
          </li>

          <li class="menu-header small text-uppercase"><span class="menu-header-text">Print</span></li>
          <!-- Cards -->
          <li class="menu-item">
            <a href="all-student-list.php" class="menu-link">
              <i class="menu-icon fa-solid fa-print"></i>
              <div data-i18n="Basic">All Student List</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="pg-wise.php" class="menu-link">
              <i class="menu-icon fa-solid fa-print"></i>
              <div data-i18n="Basic">PG Wise</div>
            </a>
          </li>

        </ul>
      </aside>
      <!-- / Menu -->

      <!-- Layout container -->
      <div class="layout-page">
        <!-- Navbar -->

        <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
          <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
            <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
              <i class="bx bx-menu bx-sm"></i>
            </a>
          </div>

          <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
            <!-- Search -->
            <div class="navbar-nav d-flex align-items-center justify-content-center align-items-center">
              <nav aria-label="breadcrumb  navbar-nav ">
                <ol class="breadcrumb mt-3">
                  <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                  <li class="breadcrumb-item">Insert</li>
                  <li class="breadcrumb-item active" aria-current="page">Student</li>
                </ol>
              </nav>
            </div>
            <!-- /Search -->

            <ul class="navbar-nav flex-row align-items-center ms-auto">
              <!-- Place this tag where you want the button to render. -->


              <!-- User -->
              <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                  <div class="avatar avatar-online">
                    <img src="../assets/img/avatars/account.png" alt class="w-px-40 h-auto rounded-circle" />
                  </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a class="dropdown-item" href="#">
                      <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                          <div class="avatar avatar-online">
                            <img src="../assets/img/avatars/account.png" alt class="w-px-40 h-auto rounded-circle" />
                          </div>
                        </div>
                        <div class="flex-grow-1">
                          <span class="fw-semibold d-block"><?php echo $_SESSION['office']; ?></span>
                          <small class="text-muted">Admin</small>
                        </div>
                      </div>
                    </a>
                  </li>
                  <li>
                    <div class="dropdown-divider"></div>
                  </li>
                  <li>
                    <a class="dropdown-item" href="../../../logout.php">
                      <i class="bx bx-power-off me-2"></i>
                      <span class="align-middle">Log Out</span>
                    </a>
                  </li>
                </ul>
              </li>
              <!--/ User -->
            </ul>
          </div>
        </nav>

        <!-- / Navbar -->

        <!-- Content wrapper -->
        <div class="content-wrapper">
          <!-- Content -->


          <div class="container-xxl flex-grow-1 container-p-y">

            <div class="row d-flex justify-content-center">
              <!-- Default Checkboxes and radios & Default checkboxes and radios -->
              <!-- start -->
              <div class="col-xl-6 ">

                <!-- HTML5 Inputs -->
                <form action="" method="POST">
                  <div class="card mb-4">
                    <h4 class="card-header text-center">ADD STUDENT</h4>
                    <div class="card-body">
                      <!-- <center><h4 class="fw-bold py-3 mb-4">Add Student</h4></center> -->
                      <div class="mb-3 row">
                        <label for="html5-text-input" class="col-md-4 col-form-label">Roll No/ Register No</label>
                        <div class="col-md-8">
                          <input class="form-control" type="text" id="html5-text-input" name="rollno" required />
                        </div>
                      </div>

                      <div class="mb-3 row">
                        <label for="html5-email-input" class="col-md-4 col-form-label">Student Name</label>
                        <div class="col-md-8">
                          <input class="form-control" type="text" name="stdname" required />
                        </div>
                      </div>

                      <div class="mb-3 row">
                        <label for="html5-email-input" class="col-md-4 col-form-label">Class</label>
                        <div class="col-md-8">
                          <select class="custom-select w-100 p-1 form-control text-dark" name="class" id="class" required>
                            <option value="">Select Class</option>
                            <option value="I PU">I PU</option>
                            <option value="II PU">II PU</option>
                          </select>
                        </div>
                      </div>

                      <div class="mb-3 row">
                        <label for="html5-email-input" class="col-md-4 col-form-label">Combination</label>
                        <div class="col-md-8">
                          <select class="custom-select w-100 p-1 form-control text-dark" name="comb" id="comb" required>
                            <option value="">Select Combination</option>
                            <option value="PCMB">PCMB</option>
                            <option value="PCMS">PCMS</option>
                            <option value="PCMC">PCMC</option>
                            <option value="BAES">BAES</option>
                            <option value="BAEC">BAEC</option>
                            <option value="HEPK">HEPK</option>
                            <option value="HEPP">HEPP</option>
                          </select>
                        </div>
                      </div>

                      <div class="mb-3 row">
                        <label for="html5-text-input" class="col-md-4 col-form-label">Father Name</label>
                        <div class="col-md-8">
                          <input class="form-control" type="text" name="fname" required />
                        </div>
                      </div>

                      <div class="mb-3 row">
                        <label for="html5-text-input" class="col-md-4 col-form-label">Gender</label>
                        <div class="col-md-8" required>
                          <input type="radio" class="gender" id="radioOption1" name="gender" value="Male" />
                          <label for="html5-text-input" class="col-md-2 col-form-label">Male</label>
                          <input type="radio" class="gender" id="radioOption2" name="gender" value="Female" />
                          <label for="html5-text-input" class="col-md-2 col-form-label">Female</label>

                        </div>
                      </div>


                      <div class="mb-3 row">
                        <label for="html5-email-input" class="col-md-4 col-form-label">Address</label>
                        <div class="col-md-8">
                          <textarea class="form-control" rows="3" name="address" required></textarea>
                        </div>
                      </div>

                      <div class="mb-3 row">
                        <label for="html5-tel-input" class="col-md-4 col-form-label">Parent's Mobile No</label>
                        <div class="col-md-8">
                          <input class="form-control" type="text" id="ph_no1" onkeyup="check_no(ph_no1);" minlength="10" maxlength="10" name="contact" required />
                        </div>
                      </div>

                      <div class="mb-3 row">
                        <label for="html5-tel-input" class="col-md-4 col-form-label">Email Id</label>
                        <div class="col-md-8">
                          <input class="form-control" type="email" id="html5-tel-input" name="email" required />
                        </div>
                      </div>


                      <div class="mb-3 row">
                        <label for="html5-tel-input" class="col-md-4 col-form-label">PG Name</label>
                        <div class="col-md-8" id="dropdown1">
                          <select name="pg" id="pg" class="custom-select w-100 p-1 form-control text-dark" required>
                            <option value="">Select PG</option>
                            <?php
                            include('../../../dbmain_conn.php');
                            if ($maincon->connect_error) {
                              echo 'connection failed';
                            }
                            $sql1 = "select code,messName from tblmess where vacancy>0  order by code";
                            $res = $maincon->query($sql1);
                            if ($res->num_rows > 0) {
                              while ($row = $res->fetch_assoc()) {
                                echo "<option value='" . $row['code'] . " " . $row['messName'] . "'>" . $row['code'] . "-" . $row['messName'] . "</option>";
                              }
                            }
                            $sql2 = "select code,messName from tblmess where vacancy=0 order by code";
                            $res = $maincon->query($sql2);
                            if ($res->num_rows > 0) {
                              while ($row = $res->fetch_assoc()) {
                                echo "<option disabled value='" . $row['code'] . " " . $row['messName'] . "'>" . $row['code'] . "-" . $row['messName'] . "</option>";
                              }
                            }
                            $maincon->close();
                            ?>
                          </select>
                        </div>

                      </div>

                      <div class="d-flex justify-content-around mt-4">
                        <div>
                          <input class="btn btn-primary" type="submit" name="submit" value="Submit">
                        </div>
                        <div>
                          <input class="btn btn-primary" type="reset" value="Reset">
                        </div>
                      </div>

                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- / Content -->

        <!-- Footer -->
        <footer class="content-footer footer bg-footer-theme ">
          <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column" style="background-color: #fff; color: #748496;">
            <div class="mb-2 mb-md-0 d-flex justify-content-center w-100 p-2 align-content-center">
              <p> <strong> © SDM PUC | Ujire, All Right Reserved.</strong></p>
            </div>
          </div>
        </footer>
        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
      </div>
      <!-- Content wrapper -->
    </div>
    <!-- / Layout page -->
  </div>

  <!-- Overlay -->
  <div class="layout-overlay layout-menu-toggle"></div>
  </div>
  <!-- / Layout wrapper -->

  <!-- Core JS -->
  <!-- build:js assets/vendor/js/core.js -->
  <script src="../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../assets/vendor/libs/popper/popper.js"></script>
  <script src="../assets/vendor/js/bootstrap.js"></script>
  <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

  <script src="../assets/vendor/js/menu.js"></script>
  <!-- endbuild -->

  <!-- Vendors JS -->

  <!-- Main JS -->
  <script src="../assets/js/main.js"></script>

  <!-- Page JS -->

  <script src="../assets/js/form-basic-inputs.js"></script>

  <!-- Place this tag in your head or just before your close body tag. -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- <script>
    const option1 = document.querySelector('input[value="Male"]');
    const option2 = document.querySelector('input[value="Female"]');
    const dropdown1 = document.getElementById('dropdown1');
    const dropdown2 = document.getElementById('dropdown2');

    
    radioOption1.addEventListener('change', () => {
      if (radioOption1.checked) {
        console.log('Radio Option 1 is checked');
        dropdown1.style.display = 'block';
        dropdown2.style.display = 'none';
      }
    });
    radioOption2.addEventListener('change', () => {
      if (radioOption2.checked) {
        console.log('Radio Option 1 is checked');
        dropdown1.style.display = 'none';
        dropdown2.style.display = 'block';
      }
    });
   
  </script> -->
  <script>
    function check_no(ph_no) {
      if (!Number.isFinite(Number(ph_no.value))) {
        ph_no.value = ph_no.value.substr(0, (ph_no.value).length - 1);
      }
    }
  </script>
  <script src="https://kit.fontawesome.com/a6bcfa9fe8.js" crossorigin="anonymous"></script>
</body>

</html>