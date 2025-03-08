<?php defined('BASEPATH') or exit('No direct script access allowed');

use Jsvrcek\ICS\CalendarExport;
use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\Exception\CalendarEventException;
use Jsvrcek\ICS\Model\CalendarAlarm;
use Jsvrcek\ICS\Model\CalendarEvent;
use Jsvrcek\ICS\Model\Description\Location;
use Jsvrcek\ICS\Model\Relationship\Attendee;
use Jsvrcek\ICS\Model\Relationship\Organizer;
use Jsvrcek\ICS\Utility\Formatter;

class Ics_file
{
    protected EA_Controller|CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->library('ics_provider');
        $this->CI->load->library('ics_calendar');
    }

    public function get_stream(array $appointment, array $service, array $provider, array $customer): string
    {
        $appointment_timezone = new DateTimeZone($provider['timezone']);

        $appointment_start = new DateTime($appointment['start_datetime'], $appointment_timezone);
        $appointment_end = new DateTime($appointment['end_datetime'], $appointment_timezone);

        // Création de l'événement.
        $event = new CalendarEvent();

        $event
            ->setStart($appointment_start)
            ->setEnd($appointment_end)
            ->setStatus('CONFIRMED')
            ->setSummary($service['name'])
            ->setUid($appointment['id_caldav_calendar'] ?: $this->generate_uid($appointment['id']));

        // Utilisation de l'adresse du provider dans le champ "location" du fichier ICS.
        $location = new Location();
        $location->setName($provider['address'] . ', ' . $provider['city'] . ' ' . $provider['zip_code']);
        $event->addLocation($location);


        $customer_link = site_url('booking/reschedule/' . $appointment['hash']);

        // Construction de la description (informations sur le provider uniquement) et ajout du texte personnalisé.
        $description = [
            '',
            lang('provider'),
            '',
            lang('name') . ': ' . $provider['first_name'] . ' ' . $provider['last_name'],
            lang('email') . ': ' . $provider['email'],
            lang('phone_number') . ': ' . $provider['phone_number'],
            lang('address') . ': ' . $provider['address'],
            lang('city') . ': ' . $provider['city'],
            lang('zip_code') . ': ' . $provider['zip_code'],
            '',
            lang('notes'),
            '',
            $appointment['notes'],
            '',
            'Merci d\'arriver 5 minutes avant l\'heure du rendez-vous',
            '',
            'Reprogrammez votre rendez-vous ici : ' . $customer_link
        ];

        $event->setDescription(implode("\\n", $description));

        $attendee = new Attendee(new Formatter());

        if (isset($customer['email']) && !empty($customer['email'])) {
            $attendee->setValue($customer['email']);
        }

        // Ajout du participant (customer) à l'événement.
        $attendee->setName($customer['first_name'] . ' ' . $customer['last_name']);
        $attendee
            ->setCalendarUserType('INDIVIDUAL')
            ->setRole('REQ-PARTICIPANT')
            ->setParticipationStatus('NEEDS-ACTION')
            ->setRsvp('TRUE');
        $event->addAttendee($attendee);

        $alarm = new CalendarAlarm();
        $alarm_datetime = clone $appointment_start;
        $alarm->setTrigger($alarm_datetime->modify('-15 minutes'));
        $alarm->setSummary('Alarm notification');
        $alarm->setDescription('This is an event reminder');
        $alarm->setAction('EMAIL');
        $alarm->addAttendee($attendee);
        $event->addAlarm($alarm);

        $alarm = new CalendarAlarm();
        $alarm_datetime = clone $appointment_start;
        $alarm->setTrigger($alarm_datetime->modify('-60 minutes'));
        $alarm->setSummary('Alarm notification');
        $alarm->setDescription('This is an event reminder');
        $alarm->setAction('EMAIL');
        $alarm->addAttendee($attendee);
        $event->addAlarm($alarm);

        $attendee = new Attendee(new Formatter());

        if (isset($provider['email']) && !empty($provider['email'])) {
            $attendee->setValue($provider['email']);
        }

        $attendee->setName($provider['first_name'] . ' ' . $provider['last_name']);
        $attendee
            ->setCalendarUserType('INDIVIDUAL')
            ->setRole('REQ-PARTICIPANT')
            ->setParticipationStatus('ACCEPTED')
            ->setRsvp('FALSE');
        $event->addAttendee($attendee);

        // Définition de l'organisateur.
        $organizer = new Organizer(new Formatter());
        $organizer->setValue($provider['email'])->setName($provider['first_name'] . ' ' . $provider['last_name']);
        $event->setOrganizer($organizer);

        // Configuration du calendrier.
        $calendar = new Ics_calendar();
        $calendar
            ->setProdId('-//EasyAppointments//Open Source Web Scheduler//EN')
            ->setTimezone(new DateTimeZone($provider['timezone']))
            ->addEvent($event);

        // Exportation du calendrier.
        $calendarExport = new CalendarExport(new CalendarStream(), new Formatter());
        $calendarExport->addCalendar($calendar);

        return $calendarExport->getStream();
    }

    public function get_unavailability_stream(array $unavailability, array $provider): string
    {
        $unavailability_timezone = new DateTimeZone($provider['timezone']);

        $unavailability_start = new DateTime($unavailability['start_datetime'], $unavailability_timezone);
        $unavailability_end = new DateTime($unavailability['end_datetime'], $unavailability_timezone);

        $event = new CalendarEvent();

        $event
            ->setStart($unavailability_start)
            ->setEnd($unavailability_end)
            ->setStatus('CONFIRMED')
            ->setSummary('Unavailability')
            ->setUid($unavailability['id_caldav_calendar'] ?: $this->generate_uid($unavailability['id']));

        $event->setDescription(str_replace("\n", "\\n", (string) $unavailability['notes']));

        $organizer = new Organizer(new Formatter());
        $organizer->setValue($provider['email'])->setName($provider['first_name'] . ' ' . $provider['last_name']);
        $event->setOrganizer($organizer);

        $calendar = new Ics_calendar();
        $calendar
            ->setProdId('-//EasyAppointments//Open Source Web Scheduler//EN')
            ->setTimezone(new DateTimeZone($provider['timezone']))
            ->addEvent($event);

        $calendarExport = new CalendarExport(new CalendarStream(), new Formatter());
        $calendarExport->addCalendar($calendar);

        return $calendarExport->getStream();
    }

    public function generate_uid(int $db_record_id): string
    {
        return 'ea-' . md5($db_record_id);
    }
}
