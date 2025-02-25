<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;


class NotificationService
{
    private $mailer;
    private $parameterBag;
    private $ics;
    private $twig;
    private $translator;

    public function __construct(MailerService $mailerService, ParameterBagInterface $parameterBag, IcsService $icsService, Environment $environment, TranslatorInterface $translator)
    {
        $this->mailer = $mailerService;
        $this->parameterBag = $parameterBag;
        $this->twig = $environment;
        $this->translator = $translator;
    }

    function createIcs(Rooms $rooms, User $user, $url, $method = 'REQUEST')
    {
        $this->ics = new IcsService();
        $this->ics->setMethod($method);
        if ($rooms->getModerator() !== $user) {
            $organizer = $rooms->getModerator()->getEmail();
        } else {
            $organizer = $rooms->getModerator()->getFirstName().'@'.$rooms->getModerator()->getLastName().'.de';
            $this->ics->setIsModerator(true);
        }
        $this->ics->add(
            array(
                'uid' => md5($rooms->getUid()),
                'location' => $this->translator->trans('Jitsi Konferenz'),
                'description' => $this->translator->trans('Sie wurden zu einer Videokonferenz auf dem Jitsi Server {server} hinzugefügt.', array('{server}' => $rooms->getServer()->getUrl())) .
                    '\n\n' .
                    $this->translator->trans('Über den beigefügten Link können Sie ganz einfach zur Videokonferenz beitreten.\nName: {name} \nModerator: {moderator} ', array('{name}' => $rooms->getName(), '{moderator}' => $rooms->getModerator()->getFirstName() . ' ' . $rooms->getModerator()->getLastName()))
                    . '\n\n' .
                    $this->translator->trans('Folgende Daten benötigen Sie um der Konferenz beizutreten:\nKonferenz ID: {id} \nIhre E-Mail-Adresse: {email}',array('{id}'=>$rooms->getUid(),'{email}'=>$user->getEmail()))
                    . '\n\n' .
                    $url .
                    '\n\n'.
                    $this->translator->trans('Sie erhalten diese E-Mail, weil Sie zu einer Videokonferenz eingeladen wurden.'),
                'dtstart' => $rooms->getStart()->format('Ymd') . "T" . $rooms->getStart()->format("His"),
                'dtend' => $rooms->getEnddate()->format('Ymd') . "T" . $rooms->getEnddate()->format("His"),
                'summary' => $rooms->getName(),
                'sequence' => $rooms->getSequence(),
                'organizer' => $organizer,
                'attendee' => $user->getEmail(),
            )
        );
        return $this->ics->toString();
    }

    function sendNotification($content, $subject, User $user, Server $server, $attachement = array()):bool
    {
        return $this->mailer->sendEmail(
            $user->getEmail(),
            $subject,
            $content,
            $server,
            $attachement
        );



    }


    function sendCron($content, $subject, User $user, Server $server):bool
    {
       return $this->mailer->sendEmail(
            $user->getEmail(),
            $subject,
            $content,
            $server
        );

    }


}
