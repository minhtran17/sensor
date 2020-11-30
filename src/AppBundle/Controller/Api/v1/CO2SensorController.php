<?php

namespace AppBundle\Controller\Api\v1;

use AppBundle\AppBundle;
use AppBundle\Entity\Alert;
use AppBundle\Entity\Sensor;
use AppBundle\Form\Type\MeasurementType;
use AppBundle\Repository\AlertRepository;
use AppBundle\Repository\SensorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use AppBundle\Repository\MeasurementRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Measurement;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class CO2SensorController
 * @package AppBundle\Controller\Api\v1
 */
class CO2SensorController extends Controller
{
    /**
     * @var string
     */
    private $sensorType;

    /**
     * @var int
     */
    private $criticalMinCount;

    /**
     * CO2SensorController constructor.
     */
    public function __construct()
    {
        $this->sensorType = Sensor::TYPE_CO2;
        $this->criticalMinCount = 3;
    }

    /**
     * @ApiDoc(
     *   resource = true,
     *   section = "Sensor",
     *   description="Get latest measurement",
     *   requirements={
     *      {
     *          "name"="uuid",
     *          "dataType"="string",
     *          "requirement"="\w+",
     *          "description"="Sensor ID"
     *      }
     *   },
     *   statusCodes = {
     *     200 = "Return successfully",
     *     404 = "Sensor with provided uuid not found"
     *   },
     * )
     *
     * @Route("/api/v1/sensors/{uuid}/measurements", methods={"GET"})
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function measurementsCollectionAction(string $uuid): JsonResponse
    {
        return $this->handleProceedWithFoundSensor($uuid, function (Sensor $sensor) {
            /** @var MeasurementRepository $repository */
            $repository = $this->getDoctrine()->getManager()->getRepository(Measurement::class);
            $measurements = $repository->getLatestMeasurements($sensor, 1);
            /** @var Measurement $measurement */
            $measurement = reset($measurements);

            if ($measurement) {
                return $this->generateMeasurementResponse($measurement);
            }

            return new JsonResponse('Measurement Not found', Response::HTTP_NOT_FOUND);
        });
    }

    /**
     * @ApiDoc(
     *   resource = true,
     *   section = "Sensor",
     *   description="Submit a new measurement",
     *   requirements={
     *      {
     *          "name"="uuid",
     *          "dataType"="string",
     *          "requirement"="\w+",
     *          "description"="Sensor ID"
     *      }
     *   },
     *   statusCodes = {
     *     200 = "Return successfully",
     *   },
     *   parameters={
     *      {"name"="value", "dataType"="integer", "required"=true, "description"="Measurement value (ppm)"}
     *   },
     * )
     *
     * @Route("/api/v1/sensors/{uuid}/measurements", methods={"POST"})
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function measurementsCreateAction(Request $request, string $uuid): JsonResponse
    {
        $measurement = new Measurement();
        $form = $this->createForm(MeasurementType::class, $measurement, ['csrf_protection' => false]);
        $form->submit(['value' => $request->request->get('value')]);
        if ($form->isValid()) {
            return $this->handleMeasurementForm($form, $uuid);
        }

        return new JsonResponse(
            [
                'message' => 'Wrong data submitted',
                'error' => trim((string) $form->getErrors(true)),
            ],
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * @ApiDoc(
     *   resource = true,
     *   section = "Sensor",
     *   description="Get status",
     *   requirements={
     *      {
     *          "name"="uuid",
     *          "dataType"="string",
     *          "requirement"="\w+",
     *          "description"="Sensor ID"
     *      }
     *   },
     *   statusCodes = {
     *     200 = "Return successfully",
     *     404 = "Sensor with provided uuid not found"
     *   },
     * )
     *
     * @Route("/api/v1/sensors/{uuid}", methods={"GET"})
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function statusAction(string $uuid): JsonResponse
    {
        return $this->handleProceedWithFoundSensor($uuid, function (Sensor $sensor) {
            /** @var SensorRepository $repository */
            $repository = $this->getDoctrine()->getManager()->getRepository(Sensor::class);

            return new JsonResponse(['status' => $repository->getCurrentStatus($sensor)]);
        });
    }

    /**
     * @ApiDoc(
     *   resource = true,
     *   section = "Sensor",
     *   description="List alerts",
     *   requirements={
     *      {
     *          "name"="uuid",
     *          "dataType"="string",
     *          "requirement"="\w+",
     *          "description"="Sensor ID"
     *      }
     *   },
     *   statusCodes = {
     *     200 = "Return successfully",
     *     404 = "Sensor with provided uuid not found"
     *   },
     *   filters={
     *      {"name"="start", "dataType"="datetime"},
     *      {"name"="end", "dataType"="datetime"},
     *      {"name"="limit", "dataType"="integer"},
     *      {"name"="offset", "dataType"="integer"},
     *   }
     * )
     *
     * @Route("/api/v1/sensors/{uuid}/alerts", methods={"GET"})
     *
     * @param string $uuid
     * @param Request $request
     * @return JsonResponse
     */
    public function alertListAction(string $uuid, Request $request): JsonResponse
    {
        return $this->handleProceedWithFoundSensor($uuid, function (Sensor $sensor) use ($request) {
            /** @var AlertRepository $repository */
            $repository = $this->getDoctrine()->getManager()->getRepository(Alert::class);
            $filters = $request->query->all();
            $alerts = $repository->listAlerts($sensor, $filters, $filters['limit'] ?? 10, $filters['offset'] ?? 0);

            return $this->generateAlertListResponse($alerts);
        });
    }

    /**
     * @ApiDoc(
     *   resource = true,
     *   section = "Sensor",
     *   description="Get metrics",
     *   requirements={
     *      {
     *          "name"="uuid",
     *          "dataType"="string",
     *          "requirement"="\w+",
     *          "description"="Sensor ID"
     *      }
     *   },
     *   statusCodes = {
     *     200 = "Return successfully",
     *     404 = "Sensor with provided uuid not found"
     *   },
     * )
     *
     * @Route("/api/v1/sensors/{uuid}/metrics", methods={"GET"})
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function metricsAction(string $uuid): JsonResponse
    {
        return $this->handleProceedWithFoundSensor($uuid, function (Sensor $sensor) {
            /** @var MeasurementRepository $repository */
            $repository = $this->getDoctrine()->getManager()->getRepository(Measurement::class);
            $result = $repository->getMaxAndAvgMeasurementByDays($sensor, 30);

            return new JsonResponse([
                'maxLast30Days' => $result['max'],
                'avgLast30Days' => $result['avg'],
            ]);
        });
    }

    /**
     * @param FormInterface $form
     * @param string $sensorId
     * @return JsonResponse
     */
    private function handleMeasurementForm(FormInterface $form, string $sensorId): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var Sensor $sensor */
        $sensor = $entityManager->getRepository(Sensor::class)->upsert($sensorId, $this->sensorType);

        /** @var Measurement $measurement */
        $measurement = $form->getData();
        $measurement->setSensor($sensor);
        $entityManager->persist($measurement);
        $entityManager->flush();

        /** @var MeasurementRepository $repository */
        $repository = $this->getDoctrine()->getManager()->getRepository(Measurement::class);
        $repository->detectCriticalStatus($sensor, $this->criticalMinCount);

        return $this->generateMeasurementResponse($measurement);
    }

    /**
     * @param Alert[] $alerts
     * @return JsonResponse
     */
    private function generateAlertListResponse(array $alerts): JsonResponse
    {
        $response = [];

        /** @var MeasurementRepository $repository */
        $measurementRepository = $this->getDoctrine()->getManager()->getRepository(Measurement::class);
        foreach ($alerts as $alert) {
            /** @var Measurement[] $measurements */
            $measurements = $measurementRepository->findBy(
                ['alert' => $alert],
                ['created' => 'DESC'],
                $this->criticalMinCount
            );

            $item = [
                'startTime' => $alert->getStart()->format(AppBundle::DATETIME_FORMAT),
                'endTime' => $alert->getEnd() ? $alert->getEnd()->format(AppBundle::DATETIME_FORMAT) : null,
            ];
            foreach ($measurements as $index => $measurement) {
                $item['mesurement'.($index + 1)] = $measurement->getValue();
            }
            $response[] = $item;
        }

        return new JsonResponse($response);
    }

    /**
     * @param string $uuid
     * @param callable $callback
     * @return JsonResponse
     */
    private function handleProceedWithFoundSensor(string $uuid, callable $callback): JsonResponse
    {
        $sensor = $this->getDoctrine()->getManager()->getRepository(Sensor::class)->find($uuid);
        if ($sensor) {
            return $callback($sensor);
        }

        return new JsonResponse('Sensor Not found', Response::HTTP_NOT_FOUND);
    }

    /**
     * @param Measurement $measurement
     * @return JsonResponse
     */
    private function generateMeasurementResponse(Measurement $measurement): JsonResponse
    {
        return new JsonResponse([
            $measurement->getSensor()->getType() => $measurement->getValue(),
            'time' => $measurement->getCreated()->format(AppBundle::DATETIME_FORMAT),
        ]);
    }
}
