<?php
require 'vendor/autoload.php';
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;

// Collect form data
$academicYear = $_POST['academicYear'] ?? '';
$eventDate = $_POST['eventDate'] ?? '';
$activityTitle = $_POST['activityTitle'] ?? '';
$venue = $_POST['venue'] ?? '';
$eventTime = $_POST['eventTime'] ?? '';
$expertName = $_POST['expertName'] ?? '';
$coordinatorName = $_POST['coordinatorName'] ?? '';
$departments = $_POST['department'] ?? [];
$classes = $_POST['class'] ?? [];
$reportFormat = $_POST['format'] ?? 'pdf';

$uploadedFiles = [];

// Handle file uploads
if (!empty($_FILES['eventImages']['name'][0])) {
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }
    foreach ($_FILES['eventImages']['tmp_name'] as $index => $tmpName) {
        $targetPath = 'uploads/' . $_FILES['eventImages']['name'][$index];
        if (move_uploaded_file($tmpName, $targetPath)) {
            $uploadedFiles[] = $targetPath;
        }
    }
}

function generateWordReport($title, $data, $uploadedFiles)
{
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();

    // Add Header with Image and Title
    $header = $section->addHeader();
    $table = $header->addTable();

    // Add Logo
    $table->addRow();
    $cell = $table->addCell(1500);
    $cell->addImage('C:\xampp\htdocs\PBL\Eventomatic\Screenshot 2025-01-21 121740.png', [
        'width' => 60,
        'height' => 50,
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
    ]);

    // Add College Name and Department
    $cell = $table->addCell(8000);
    $cell->addText("D. Y. PATIL COLLEGE OF ENGINEERING, AKURDI, PUNE-44.", ['bold' => true, 'size' => 13], ['alignment' => 'center']);
    $cell->addText("DEPARTMENT OF COMPUTER ENGINEERING", ['bold' => true, 'size' => 13], ['alignment' => 'center']);

    // Add Line below header
    $header->addText(str_repeat('_', 80), null, ['alignment' => 'center']);

    // Generate Date
    $currentDate = date('d-m-Y');

    // Add DYPCOE line with Date properly aligned
    $table = $section->addTable();
    $table->addRow();

    // Left-aligned academic year
    $leftCell = $table->addCell(8000);
    $leftCell->addText("DYPCOE/COMP/{$data['academicYear']}", ['bold' => true]);

    // Right-aligned date
    $rightCell = $table->addCell(2000);
    $rightCell->addText("Date: $currentDate", ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);

    // "To," and "The Principal" text formatting
    $section->addTextBreak(1);
    $section->addText("To,", ['bold' => true]);
    $section->addText("The Principal", ['bold' => true]);

    // Subject Line Formatting
    $subject = "Subject: Report on ";
    $subject .= "<strong>{$data['activityTitle']}</strong> held on <strong>{$data['eventDate']}</strong> for ";
    $departmentsAndClasses = [];
    foreach ($data['departments'] as $index => $department) {
        $class = $data['classes'][$index];
        $departmentsAndClasses[] = "{$class} {$department} Students";
    }
    $subject .= implode(' and ', $departmentsAndClasses);

    // Add subject text with bold formatting for event name and date
    $textRun = $section->addTextRun();
    $textRun->addText("Subject: ",['bold' => true]);
    $textRun->addText("Report on ");
    $textRun->addText($data['activityTitle'], ['bold' => true]);
    $textRun->addText(" held on ");
    $textRun->addText($data['eventDate'], ['bold' => true]);
    $textRun->addText(" for " . implode(' and ', $departmentsAndClasses));

    // Event details
    $section->addText("Event Date: " . $data['eventDate']);
    $section->addText("Activity Title: " . $data['activityTitle']);
    $section->addText("Venue: " . $data['venue']);
    $section->addText("Event Time: " . $data['eventTime']);
    $section->addText("Expert Name: " . $data['expertName']);
    $section->addText("Coordinator Name: " . $data['coordinatorName']);



    // Add up to 4 images if available
    $imageCount = 0;
    foreach ($uploadedFiles as $filePath) {
        if (file_exists($filePath) && $imageCount < 4) {
            $section->addImage($filePath, ['width' => 300, 'height' => 200]);
            $imageCount++;
        }
    }

    $fileName = "{$data['activityTitle']}-" . date('d-m-Y', strtotime($data['eventDate'])) . '.docx';
    $filePath = "reports/$fileName";

    if (!is_dir('reports')) {
        mkdir('reports', 0777, true);
    }

    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($filePath);

    header('Location: ' . $filePath);
    exit();
}

function generatePDFReport($title, $data, $uploadedFiles)
{
    $currentDate = date('d-m-Y');

    $html = "
        <table style='width:100%;'>
            <tr>
                <td style='width:10%;'>
    ";

    $imagePath = 'C:/xampp/htdocs/PBL/Eventomatic/Screenshot 2025-01-21 121740.png';
    if (file_exists($imagePath)) {
        $imgData = base64_encode(file_get_contents($imagePath)); 
        $imgSrc = 'data:image/png;base64,' . $imgData;
        $html .= "<img src='$imgSrc' width='90' height='70' />";
    }

    $html .= "
                </td>
                <td style='width:90%;'>
                    <p style='text-align:center; font-weight:bold; font-size:18px;'>D. Y. PATIL COLLEGE OF ENGINEERING, AKURDI, PUNE-44.</p>
                    <p style='text-align:center; font-weight:bold; font-size:18px;'>DEPARTMENT OF COMPUTER ENGINEERING</p>
                </td>
            </tr>
        </table>
        <hr style='border:1px solid black; margin-top: 5px;'>

        <table style='width:100%;'>
            <tr>
                <td style='font-weight:bold;'>DYPCOE/COMP/{$data['academicYear']}</td>
                <td style='text-align:right; font-weight:bold;'>Date: $currentDate</td>
            </tr>
        </table>

        <p style='font-weight:bold;'>To,</p>
        <p style='font-weight:bold;'>The Principal</p>
    ";

    // Subject Line Formatting
    $subject = "<strong>Subject:</strong> Report on <strong>{$data['activityTitle']}</strong> held on <strong>{$data['eventDate']}</strong> for ";
    $departmentsAndClasses = [];
    foreach ($data['departments'] as $index => $department) {
        $class = $data['classes'][$index];
        $departmentsAndClasses[] = "{$class} {$department} Students";
    }
    $subject .= implode(' and ', $departmentsAndClasses);

    $html .= "<p>$subject</p>";

    // Event details
    $html .= "<p><strong>Event Date:</strong> {$data['eventDate']}</p>";
    $html .= "<p><strong>Activity Title:</strong> {$data['activityTitle']}</p>";
    $html .= "<p><strong>Venue:</strong> {$data['venue']}</p>";
    $html .= "<p><strong>Event Time:</strong> {$data['eventTime']}</p>";
    $html .= "<p><strong>Expert Name:</strong> {$data['expertName']}</p>";
    $html .= "<p><strong>Coordinator Name:</strong> {$data['coordinatorName']}</p>";

    $imageCount = 0;
    foreach ($uploadedFiles as $filePath) {
        if (file_exists($filePath) && $imageCount < 4) {
            $imgData = base64_encode(file_get_contents($filePath)); 
            $imgSrc = 'data:image/jpeg;base64,' . $imgData;
            $html .= "<img src='$imgSrc' style='width:300px;height:200px;margin:10px;display:block;'>";
            $imageCount++;
        }
    }

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $fileName = "{$data['activityTitle']}-" . date('d-m-Y', strtotime($data['eventDate'])) . '.pdf';
    $filePath = "reports/$fileName";

    if (!is_dir('reports')) {
        mkdir('reports', 0777, true);
    }

    file_put_contents($filePath, $dompdf->output());

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    readfile($filePath);
    exit();
}

$data = [
    'academicYear' => $academicYear,
    'eventDate' => $eventDate,
    'activityTitle' => $activityTitle,
    'venue' => $venue,
    'eventTime' => $eventTime,
    'expertName' => $expertName,
    'coordinatorName' => $coordinatorName,
    'departments' => $departments,
    'classes' => $classes
];

if ($reportFormat === 'word') {
    generateWordReport('Activity Report', $data, $uploadedFiles);
} else {
    generatePDFReport('Activity Report', $data, $uploadedFiles);
}
?>
