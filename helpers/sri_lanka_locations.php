<?php
/**
 * Sri Lanka provinces and towns for checkout address selection.
 * district = administrative district stored on orders (existing schema).
 */

function ezymart_sl_provinces(): array
{
    return [
        'Western',
        'Central',
        'Southern',
        'Northern',
        'Eastern',
        'North Western',
        'North Central',
        'Uva',
        'Sabaragamuwa',
    ];
}

/**
 * @return array<string, list<array{town:string, district:string}>>
 */
function ezymart_sl_towns_by_province(): array
{
    return [
        'Western' => [
            ['town' => 'Colombo', 'district' => 'Colombo'],
            ['town' => 'Dehiwala-Mount Lavinia', 'district' => 'Colombo'],
            ['town' => 'Moratuwa', 'district' => 'Colombo'],
            ['town' => 'Sri Jayawardenepura Kotte', 'district' => 'Colombo'],
            ['town' => 'Battaramulla', 'district' => 'Colombo'],
            ['town' => 'Maharagama', 'district' => 'Colombo'],
            ['town' => 'Nugegoda', 'district' => 'Colombo'],
            ['town' => 'Kolonnawa', 'district' => 'Colombo'],
            ['town' => 'Kaduwela', 'district' => 'Colombo'],
            ['town' => 'Homagama', 'district' => 'Colombo'],
            ['town' => 'Gampaha', 'district' => 'Gampaha'],
            ['town' => 'Negombo', 'district' => 'Gampaha'],
            ['town' => 'Kelaniya', 'district' => 'Gampaha'],
            ['town' => 'Wattala', 'district' => 'Gampaha'],
            ['town' => 'Ja-Ela', 'district' => 'Gampaha'],
            ['town' => 'Katunayake', 'district' => 'Gampaha'],
            ['town' => 'Minuwangoda', 'district' => 'Gampaha'],
            ['town' => 'Divulapitiya', 'district' => 'Gampaha'],
            ['town' => 'Kalutara', 'district' => 'Kalutara'],
            ['town' => 'Panadura', 'district' => 'Kalutara'],
            ['town' => 'Horana', 'district' => 'Kalutara'],
            ['town' => 'Beruwala', 'district' => 'Kalutara'],
            ['town' => 'Aluthgama', 'district' => 'Kalutara'],
            ['town' => 'Bentota', 'district' => 'Kalutara'],
            ['town' => 'Matugama', 'district' => 'Kalutara'],
        ],
        'Central' => [
            ['town' => 'Kandy', 'district' => 'Kandy'],
            ['town' => 'Peradeniya', 'district' => 'Kandy'],
            ['town' => 'Gampola', 'district' => 'Kandy'],
            ['town' => 'Kundasale', 'district' => 'Kandy'],
            ['town' => 'Teldeniya', 'district' => 'Kandy'],
            ['town' => 'Matale', 'district' => 'Matale'],
            ['town' => 'Dambulla', 'district' => 'Matale'],
            ['town' => 'Galewela', 'district' => 'Matale'],
            ['town' => 'Nuwara Eliya', 'district' => 'Nuwara Eliya'],
            ['town' => 'Hatton', 'district' => 'Nuwara Eliya'],
            ['town' => 'Talawakele', 'district' => 'Nuwara Eliya'],
            ['town' => 'Ginigathena', 'district' => 'Nuwara Eliya'],
        ],
        'Southern' => [
            ['town' => 'Galle', 'district' => 'Galle'],
            ['town' => 'Hikkaduwa', 'district' => 'Galle'],
            ['town' => 'Ambalangoda', 'district' => 'Galle'],
            ['town' => 'Elpitiya', 'district' => 'Galle'],
            ['town' => 'Matara', 'district' => 'Matara'],
            ['town' => 'Weligama', 'district' => 'Matara'],
            ['town' => 'Mirissa', 'district' => 'Matara'],
            ['town' => 'Akuressa', 'district' => 'Matara'],
            ['town' => 'Hambantota', 'district' => 'Hambantota'],
            ['town' => 'Tangalle', 'district' => 'Hambantota'],
            ['town' => 'Tissamaharama', 'district' => 'Hambantota'],
            ['town' => 'Ambalantota', 'district' => 'Hambantota'],
        ],
        'Northern' => [
            ['town' => 'Jaffna', 'district' => 'Jaffna'],
            ['town' => 'Nallur', 'district' => 'Jaffna'],
            ['town' => 'Chavakachcheri', 'district' => 'Jaffna'],
            ['town' => 'Kilinochchi', 'district' => 'Kilinochchi'],
            ['town' => 'Mannar', 'district' => 'Mannar'],
            ['town' => 'Vavuniya', 'district' => 'Vavuniya'],
            ['town' => 'Mullaitivu', 'district' => 'Mullaitivu'],
            ['town' => 'Point Pedro', 'district' => 'Jaffna'],
        ],
        'Eastern' => [
            ['town' => 'Batticaloa', 'district' => 'Batticaloa'],
            ['town' => 'Kattankudy', 'district' => 'Batticaloa'],
            ['town' => 'Ampara', 'district' => 'Ampara'],
            ['town' => 'Kalmunai', 'district' => 'Ampara'],
            ['town' => 'Sainthamaruthu', 'district' => 'Ampara'],
            ['town' => 'Dehiaththakandiya', 'district' => 'Ampara'],
            ['town' => 'Trincomalee', 'district' => 'Trincomalee'],
            ['town' => 'Kinniya', 'district' => 'Trincomalee'],
        ],
        'North Western' => [
            ['town' => 'Kurunegala', 'district' => 'Kurunegala'],
            ['town' => 'Kuliyapitiya', 'district' => 'Kurunegala'],
            ['town' => 'Nikaweratiya', 'district' => 'Kurunegala'],
            ['town' => 'Puttalam', 'district' => 'Puttalam'],
            ['town' => 'Chilaw', 'district' => 'Puttalam'],
            ['town' => 'Wennappuwa', 'district' => 'Puttalam'],
        ],
        'North Central' => [
            ['town' => 'Anuradhapura', 'district' => 'Anuradhapura'],
            ['town' => 'Medawachchiya', 'district' => 'Anuradhapura'],
            ['town' => 'Polonnaruwa', 'district' => 'Polonnaruwa'],
            ['town' => 'Hingurakgoda', 'district' => 'Polonnaruwa'],
        ],
        'Uva' => [
            ['town' => 'Badulla', 'district' => 'Badulla'],
            ['town' => 'Bandarawela', 'district' => 'Badulla'],
            ['town' => 'Hali-Ela', 'district' => 'Badulla'],
            ['town' => 'Monaragala', 'district' => 'Monaragala'],
            ['town' => 'Wellawaya', 'district' => 'Monaragala'],
            ['town' => 'Bibile', 'district' => 'Monaragala'],
        ],
        'Sabaragamuwa' => [
            ['town' => 'Ratnapura', 'district' => 'Ratnapura'],
            ['town' => 'Balangoda', 'district' => 'Ratnapura'],
            ['town' => 'Embilipitiya', 'district' => 'Ratnapura'],
            ['town' => 'Kegalle', 'district' => 'Kegalle'],
            ['town' => 'Mawanella', 'district' => 'Kegalle'],
            ['town' => 'Warakapola', 'district' => 'Kegalle'],
            ['town' => 'Rambukkana', 'district' => 'Kegalle'],
        ],
    ];
}

function ezymart_sl_locations_for_js(): array
{
    return ezymart_sl_towns_by_province();
}

function ezymart_sl_find_province_for_district(string $district): ?string
{
    $district = trim($district);
    if ($district === '') {
        return null;
    }

    foreach (ezymart_sl_towns_by_province() as $province => $towns) {
        foreach ($towns as $row) {
            if (strcasecmp($row['district'], $district) === 0) {
                return $province;
            }
        }
    }

    return null;
}

function ezymart_sl_find_province_for_town(string $town, ?string $district = null): ?string
{
    $town = trim($town);
    if ($town === '') {
        return null;
    }

    foreach (ezymart_sl_towns_by_province() as $province => $towns) {
        foreach ($towns as $row) {
            if (strcasecmp($row['town'], $town) !== 0) {
                continue;
            }
            if ($district !== null && $district !== '' && strcasecmp($row['district'], $district) !== 0) {
                continue;
            }
            return $province;
        }
    }

    if ($district !== null && $district !== '') {
        return ezymart_sl_find_province_for_district($district);
    }

    return null;
}
