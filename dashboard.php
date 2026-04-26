<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parasocial</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin:10px;
            font-family: 'Trebuchet MS', Tahoma, Geneva, Verdana, sans-serif;
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        .hero-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image:
                radial-gradient(circle at 20% 50%, white 1px, transparent 1px),
                radial-gradient(circle at 80% 80%, white 1px, transparent 1px);
            background-size: 50px 50px;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            padding-top: 80px;
        }

        .tagline {
            font-size: 1.5em;
            color: white;
            margin-top: 10px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 450px;
            margin: 40px auto;
        }

        .login-title {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 30px;
            font-weight: bold;
        }

        .w3-input {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            font-size: 1em;
            transition: all 0.3s;
        }

        .w3-input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
		
		.brand {
			position: fixed;
			top: 20px;
			left: 50px;
			color: white;
			font-size: 3em;
			font-weight: bold;
			letter-spacing: 1px;
			z-index: 1000;
			text-shadow: 1px 1px 4px rgba(0,0,0,0.3);
		}

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 14px;
            color: white;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-guest {
            background: white;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 14px;
            color: #667eea;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-guest:hover {
            background: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0;
            color: #999;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e0e0e0;
        }

        .divider span {
            padding: 0 15px;
            font-size: 0.9em;
        }

        .error-message {
            color: #b00020;
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
        }
    </style>
    <body>
        Henlo Worlnd
    </body>