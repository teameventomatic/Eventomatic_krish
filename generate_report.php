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
$honourableNames = $_POST['honourable_name'] ?? [];
$designations = $_POST['designationselect'] ?? [];
$eventTime = $_POST['eventTime'] ?? '';
$expertName = $_POST['expertName'] ?? '';
$principalIndex = $_POST['principalIndex'] ?? 0; // Default value if not set
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
        $targetPath = 'uploads/' . basename($_FILES['eventImages']['name'][$index]);
        if (move_uploaded_file($tmpName, $targetPath)) {
            $uploadedFiles[] = $targetPath;
        }
    }
}

function generateWordReport($data, $uploadedFiles)
{
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();

    // Add Header with Image and Title
    $header = $section->addHeader();
    $table = $header->addTable();

    // Add Logo
    $table->addRow();
    $cell = $table->addCell(1500);
    $imagePath = 'C:/xampp/htdocs/PBL/Eventomatic/Eventomatic/Screenshot 2025-01-21 121740.png';
    if (file_exists($imagePath)) {
        $cell->addImage($imagePath, [
            'width' => 60,
            'height' => 50,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
        ]);
    } else {
        $cell->addText("Logo not found", ['bold' => true, 'size' => 10], ['alignment' => 'center']);
    }

    // Add College Name and Department
    $cell = $table->addCell(8000);
    $cell->addText("D. Y. PATIL COLLEGE OF ENGINEERING, AKURDI, PUNE-44.", ['bold' => true, 'size' => 18], ['alignment' => 'center']);
    $cell->addText("Department Of " . (!empty($data['departments']) ? $data['departments'][0] : 'Unknown Department'), ['bold' => true, 'size' => 18], ['alignment' => 'center']);

    // Add Line below header
    $header->addText(str_repeat('_', 80), null, ['alignment' => 'center']);

    // Generate Date
    $currentDate = date('d-m-Y');

    // Add DYPCOE line with Date properly aligned
    $table = $section->addTable();
    $table->addRow();

    // Left-aligned academic year
    $leftCell = $table->addCell(8000);
    $leftCell->addText("DYPCOE/{$data['academicYear']}", ['bold' => true]);

    // Right-aligned date
    $rightCell = $table->addCell(2000);
    $rightCell->addText("Date: $currentDate", ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);

    // Add "To, The Principal" line
    $section->addTextBreak(1);
    $section->addText("To,", ['bold' => true]);
    $section->addText("The Principal", ['bold' => true]);
    $section->addTextBreak(1);

    // Add respectful salutation
    $section->addText("Respected Sir/Madam,");
    $section->addTextBreak(1);

    // Add event paragraph
    $departmentsAndClassesText = implode(' and ', array_map(function($department, $class) {
        return "{$class} {$department}";
    }, $data['departments'], $data['classes']));
    
    $section->addText("The Department of {$data['departments'][0]} successfully organized an event titled \"{$data['activityTitle']}\" conducted on {$data['eventDate']} for students of {$departmentsAndClassesText}.");
    $section->addText("The session, held at {$data['venue']} from {$data['eventTime']} onwards, was conducted by {$data['expertName']}, an esteemed expert in the field. The event was coordinated by {$data['coordinatorName']} and was aimed at enhancing students' understanding of key concepts, real-world applications, and future trends. This insightful session provided students with valuable knowledge, fostering their technical skills and encouraging innovation in the field.");
    
    // Add outcomes
    $section->addText("Outcomes:", ['bold' => true]);
    $section->addText("1: Participants gained deeper insights into the topic of the event, titled \"{$data['activityTitle']}\", enriching their knowledge on key concepts.");
    $section->addText("2: The event contributed to developing skills related to understanding and engaging with the core aspects of the \"{$data['activityTitle']}\", enhancing attendees' proficiency in the subject.");
    $section->addText("3: Attendees actively participated in discussions and activities centered around the \"{$data['activityTitle']}\", fostering collaboration and idea-sharing among participants.");
    $section->addText("4: The event provided a platform for students to delve into the specifics of the topic, resulting in a stronger understanding of its broader implications and applications.");

    // Add event snapshots
    $section->addText("Event Snapshots:", ['bold' => true]);
    $imageCount = 0;
    foreach ($uploadedFiles as $filePath) {
        if (file_exists($filePath) && $imageCount < 3) {
            $section->addImage($filePath, ['width' => 300, 'height' => 200]);
            $imageCount++;
        }
    }

    // Footer with alignment
    $eventCoordinator = $data['coordinatorName'];
    $hodName = isset($data['honourable_name'][0]) ? $data['honourable_name'][0] : 'HOD';
    $principalName = isset($data['honourable_name'][1]) ? $data['honourable_name'][1] : 'Principal';

    $footer = $section->addFooter();
    $footerTable = $footer->addTable();
    $footerTable->addRow();
    $footerTable->addCell(3000)->addText($eventCoordinator . "\nEvent Coordinator", ['bold' => true], ['alignment' => 'left']);
    $footerTable->addCell(3000)->addText($hodName . "\nHead of Department", ['bold' => true], ['alignment' => 'center']);
    $footerTable->addCell(3000)->addText($principalName . "\nPrincipal", ['bold' => true], ['alignment' => 'right']);

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

function generatePDFReport($data, $uploadedFiles)
{
    $currentDate = date('d-m-Y');

    $html = "
        <table style='width:100%;'>
            <tr>
                <td style='width:10%;'>
    ";

    $imagePath = 'C:/xampp/htdocs/PBL/Eventomatic/Eventomatic/Screenshot 2025-01-21 121740.png';
    if (file_exists($imagePath)) {
        $imgData = base64_encode(file_get_contents($imagePath)); 
        $imgSrc = 'data:image/png;base64,' . $imgData;
        $html .= "<img src='$imgSrc' width='90' height='70' />";
    }

    $html .= "
                </td>
                <td style='width:90%;'>
                    <p style='text-align:center; font-weight:bold; font-size:18px;'>D. Y. PATIL COLLEGE OF ENGINEERING, AKURDI, PUNE-44.</p>
                    <p style='text-align:center; font-weight:bold; font-size:18px;'>Department Of ";
    
    // Use the first selected department for the header
    $firstDepartment = !empty($data['departments']) ? $data['departments'][0] : 'Unknown Department';
    $html .= "{$firstDepartment}</p>
                </td>
            </tr>
        </table>
        <hr style='border:1px solid black; margin-top: 5px;'>

        <table style='width:100%;'>
            <tr>
                <td style='font-weight:bold;'>DYPCOE/{$data['academicYear']}</td>
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

    // Add break and respectful salutation
    $html .= "<br><p>Respected Sir/Madam,</p><br>";

    // Add event paragraph
    $departmentsAndClassesText = implode(' and ', array_map(function($department, $class) {
        return "{$class} {$department}";
    }, $data['departments'], $data['classes']));
    
    $html .= "<p>The Department of {$firstDepartment} successfully organized an event titled &ldquo;{$data['activityTitle']}&rdquo; conducted on {$data['eventDate']} for students of {$departmentsAndClassesText}.</p>";
    $html .= "<p>The session, held at {$data['venue']} from {$data['eventTime']} onwards, was conducted by {$data['expertName']}, an esteemed expert in the field. The event was coordinated by {$data['coordinatorName']} and was aimed at enhancing students' understanding of key concepts, real-world applications, and future trends. This insightful session provided students with valuable knowledge, fostering their technical skills and encouraging innovation in the field.</p>";
    $html .= "<p><strong>Outcomes:</strong></p>";
    $html .= "<p>1: Participants gained deeper insights into the topic of the event, titled &ldquo;{$data['activityTitle']}&rdquo;, enriching their knowledge on key concepts.</p>";
    $html .= "<p>2: The event contributed to developing skills related to understanding and engaging with the core aspects of the &ldquo;{$data['activityTitle']}&rdquo;, enhancing attendees' proficiency in the subject.</p>";
    $html .= "<p>3: Attendees actively participated in discussions and activities centered around the &ldquo;{$data['activityTitle']}&rdquo;, fostering collaboration and idea-sharing among participants.</p>";
    $html .= "<p>4: The event provided a platform for students to delve into the specifics of the topic, resulting in a stronger understanding of its broader implications and applications.</p>";
    $html .= "<p><strong>Event Snapshots:</strong></p>";

    // Add images
    $imageCount = 0;
    foreach ($uploadedFiles as $filePath) {
        if (file_exists($filePath) && $imageCount < 3) {
            $imgData = base64_encode(file_get_contents($filePath)); 
            $imgSrc = 'data:image/jpeg;base64,' . $imgData;
            $html .= "<img src='$imgSrc' style='width:300px;height:200px;margin-top:40px; margin-right:20px;display:block;'>";
            $imageCount++;
        }
    }

    // Fetch Honourable Person selections
    $eventCoordinator = $data['coordinatorName'];
    $hodName = isset($data['honourable_name'][0]) ? $data['honourable_name'][0] : 'HOD';
    $principalName = isset($data['honourable_name'][1]) ? $data['honourable_name'][1] : 'Principal';

    // Footer with alignment (without table)
    $html .= "
        <div style='width:100%; margin-top:50px;'>
            <div style='float:left; width:33%; font-size:12px; text-align:left; font-weight:bold;'>{$eventCoordinator}<br><span style='font-size:12px;'>Event Coordinator</span></div>
            <div style='float:left; width:33%; text-align:center; font-weight:bold; font-size:12px;'>{$hodName}<br><span style='font-size:12px;'>Head of Department</span></div>
            <div style='float:right; width:33%; text-align:right; font-weight:bold; font-size:12px;'>{$principalName}<br><span style='font-size:12px;'>Principal</span></div>
        </div>
        <div style='clear:both;'></div>
    ";

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
    'honourable_name' => $honourableNames,
    'eventTime' => $eventTime,
    'expertName' => $expertName,
    'coordinatorName' => $coordinatorName,
    'departments' => $departments,
    'classes' => $classes,
    'organizedby' => $_POST['organizedby'] ?? ''
];

if ($reportFormat === 'word') {
    generateWordReport($data, $uploadedFiles);
} else {
    generatePDFReport($data, $uploadedFiles);
}

?>