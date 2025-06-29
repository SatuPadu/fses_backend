<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nomination Locked</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .alert {
            background-color: #FEF3C7;
            border: 1px solid #F59E0B;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box {
            background-color: #F3F4F6;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nomination Locked</h1>
        </div>
        
        <p>Dear Research Supervisor,</p>
        
        <div class="alert">
            <strong>Important Notice:</strong> The examiner nomination for your student has been locked and no further modifications are allowed.
        </div>
        
        <h3>Student Information:</h3>
        <div class="info-box">
            <p><strong>Student Name:</strong> {{ $student->name }}</p>
            <p><strong>Student ID:</strong> {{ $student->matric_number }}</p>
            <p><strong>Program:</strong> {{ $student->program->program_name ?? 'N/A' }}</p>
            <p><strong>Department:</strong> {{ $student->department }}</p>
        </div>
        
        <h3>Evaluation Details:</h3>
        <div class="info-box">
            <p><strong>Period:</strong> Semester {{ $evaluation->semester }}, {{ $evaluation->academic_year }}</p>
            <p><strong>Status:</strong> {{ $evaluation->nomination_status }}</p>
            <p><strong>Locked At:</strong> {{ $evaluation->locked_at ? $evaluation->locked_at->format('F j, Y \a\t g:i A') : 'N/A' }}</p>
        </div>
        
        <h3>Committee Members:</h3>
        <div class="info-box">
            @if($evaluation->examiner1)
                <p><strong>Examiner 1:</strong> {{ $evaluation->examiner1->title }} {{ $evaluation->examiner1->name }}</p>
            @endif
            @if($evaluation->examiner2)
                <p><strong>Examiner 2:</strong> {{ $evaluation->examiner2->title }} {{ $evaluation->examiner2->name }}</p>
            @endif
            @if($evaluation->examiner3)
                <p><strong>Examiner 3:</strong> {{ $evaluation->examiner3->title }} {{ $evaluation->examiner3->name }}</p>
            @endif
            @if($evaluation->chairperson)
                <p><strong>Chairperson:</strong> {{ $evaluation->chairperson->title }} {{ $evaluation->chairperson->name }}</p>
            @endif
        </div>
        
        <p>The examiner nomination has been finalized and locked. No further changes can be made to the committee composition. The evaluation process will proceed according to the established timeline.</p>
        
        <p>If you have any questions about this nomination or the evaluation process, please contact the program coordinator or the postgraduate academic manager.</p>
        
        <div class="footer">
            <p>Thank you for your cooperation!</p>
            <p>This is an automated notification from the FSES Evaluation System.</p>
        </div>
    </div>
</body>
</html> 