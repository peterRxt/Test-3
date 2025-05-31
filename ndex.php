<?php
// CONFIG
$apiKey = "7e6dfd53ad4e3bf92f2a3a356aea5538";
$today = date("Y-m-d");
$limit = 10;

// GET today’s matches
$matchesUrl = "https://v3.football.api-sports.io/fixtures?date=$today&status=NS";
$headers = [
    "x-apisports-key: $apiKey"
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $matchesUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
]);
$response = curl_exec($curl);
curl_close($curl);

$matchesData = json_decode($response, true);
$fixtures = $matchesData["response"] ?? [];

// Helper: Get last 5 results of a team
function getTeamForm($teamId, $apiKey) {
    $formUrl = "https://v3.football.api-sports.io/fixtures?team=$teamId&last=5";
    $headers = ["x-apisports-key: $apiKey"];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $formUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    
    $formData = json_decode($response, true);
    $fixtures = $formData["response"] ?? [];
    
    $form = "";
    foreach ($fixtures as $match) {
        $winner = $match["teams"]["home"]["winner"] ?? false;
        $isHome = $match["teams"]["home"]["id"] == $teamId;
        $teamWin = $winner === $isHome;
        if ($match["goals"]["home"] == $match["goals"]["away"]) {
            $form .= "D";
        } elseif ($teamWin) {
            $form .= "W";
        } else {
            $form .= "L";
        }
    }
    return $form;
}

// Filter out-of-form teams
$outOfFormTeams = [];

foreach ($fixtures as $match) {
    foreach (["home", "away"] as $side) {
        $team = $match["teams"][$side];
        $teamId = $team["id"];
        $teamName = $team["name"];
        $logo = $team["logo"];
        $time = $match["fixture"]["date"];
        
        $form = getTeamForm($teamId, $apiKey);
        $losses = substr_count($form, "L");
        
        if ($losses >= 3 && count($outOfFormTeams) < $limit) {
            $outOfFormTeams[] = [
                "name" => $teamName,
                "logo" => $logo,
                "form" => $form,
                "time" => date("H:i", strtotime($time)),
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Out-of-Form Football Teams Today</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0; padding: 20px;
        }
        h1 {
            text-align: center;
        }
        .search {
            margin: 20px auto;
            text-align: center;
        }
        input {
            padding: 10px;
            width: 300px;
            font-size: 16px;
        }
        .team {
            background: #fff;
            padding: 15px;
            margin: 15px auto;
            border-left: 5px solid red;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 500px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .team img {
            width: 50px;
        }
        .team-info {
            flex: 1;
        }
        .form {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>⚽ Out-of-Form Teams Playing Today</h1>

    <div class="search">
        <input type="text" id="searchBox" placeholder="Search team...">
    </div>

    <div id="teamList">
        <?php if (empty($outOfFormTeams)): ?>
            <p style="text-align:center;">No out-of-form teams found today.</p>
        <?php else: ?>
            <?php foreach ($outOfFormTeams as $team): ?>
                <div class="team">
                    <img src="<?= $team['logo'] ?>" alt="logo">
                    <div class="team-info">
                        <strong class="team-name"><?= $team['name'] ?></strong><br>
                        Time: <?= $team['time'] ?><br>
                        Form: <span class="form"><?= $team['form'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        const searchBox = document.getElementById("searchBox");
        searchBox.addEventListener("input", function () {
            const keyword = this.value.toLowerCase();
            const teams = document.querySelectorAll(".team");

            teams.forEach(team => {
                const name = team.querySelector(".team-name").textContent.toLowerCase();
                if (name.includes(keyword)) {
                    team.style.display = "flex";
                } else {
                    team.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>
